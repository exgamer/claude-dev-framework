package tariff

import (
	"context"
	"errors"
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func NewCreateTariffWorkflow(
	txManager CreateTariffTxManager,
	priceLimitRepository tariffdomain.PriceLimitRepository,
	tariffNotifier TariffNotifier,
	publishOutboxEventCommand *outboxdomain.PublishOutboxTariffEventCommand,
) *CreateTariffWorkflow {
	return &CreateTariffWorkflow{
		txManager:                 txManager,
		priceLimitRepository:      priceLimitRepository,
		tariffNotifier:            tariffNotifier,
		publishOutboxEventCommand: publishOutboxEventCommand,
	}
}

// CreateTariffWorkflow Создание тарифа на активной парковке с ценой в пределах лимита реестра парковок; о новом тарифе узнают партнёрский биллинг и брокер (tariff.created)
type CreateTariffWorkflow struct {
	txManager                 CreateTariffTxManager
	priceLimitRepository      tariffdomain.PriceLimitRepository
	tariffNotifier            TariffNotifier
	publishOutboxEventCommand *outboxdomain.PublishOutboxTariffEventCommand
}

func (w *CreateTariffWorkflow) Exec(ctx context.Context, params *CreateTariffParams) (*tariffdomain.Tariff, error) {
	limit, err := w.priceLimitRepository.GetByParkingID(ctx, params.ParkingID)
	if err != nil {
		return nil, err
	}

	// Реестр не задал лимит — цена не ограничена
	if limit != nil && params.Price > limit.MaxPrice {
		message := fmt.Sprintf("цена выше лимита парковки в реестре парковок (%d)", limit.MaxPrice)

		return nil, exception.NewValidationException(map[string]any{"price": message}, false)
	}

	var created *tariffdomain.Tariff
	var outboxEvent *outboxdomain.OutboxTariffEvent

	err = w.txManager.Exec(ctx, func(
		ctx context.Context,
		parkingRepository parkingdomain.Repository,
		tariffRepository tariffdomain.Repository,
		outboxRepository outboxdomain.Repository,
	) error {
		parking, err := parkingRepository.GetByID(ctx, params.ParkingID)
		if err != nil {
			return err
		}

		if parking == nil {
			return exception.NewValidationException(map[string]any{"parking_id": "парковка не найдена"}, false)
		}

		if !parking.IsActive {
			return exception.NewForbiddenException(errors.New("парковка отключена"), false)
		}

		// Новый тариф — не по умолчанию: флаг ставится отдельным запросом
		created, err = tariffRepository.Create(ctx, &tariffdomain.Tariff{
			ParkingID:    params.ParkingID,
			Name:         params.Name,
			Currency:     params.Currency,
			IsActive:     true,
			GraceMinutes: params.GraceMinutes,
			Price:        params.Price,
		})
		if err != nil {
			return err
		}

		event := outboxdomain.NewTariffCreatedEvent(outboxdomain.CreatedTariff{
			TariffID:     created.ID,
			ParkingID:    created.ParkingID,
			Name:         created.Name,
			Currency:     created.Currency,
			GraceMinutes: created.GraceMinutes,
			Price:        created.Price,
		})

		outboxEvent, err = outboxRepository.Create(ctx, event)

		return err
	})
	if err != nil {
		return nil, err
	}

	// Тариф уже сохранён: уход клиента не отменяет уведомление и отправку события, их сбой не отменяет создание
	if err := w.tariffNotifier.NotifyCreated(context.WithoutCancel(ctx), created); err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "partnerbilling_tariffs"})
	}

	if err := w.publishOutboxEventCommand.Exec(context.WithoutCancel(ctx), outboxEvent.ID); err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "outbox_tariff_event"})
	}

	return created, nil
}
