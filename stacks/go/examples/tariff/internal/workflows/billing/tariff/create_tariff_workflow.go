package tariff

import (
	"context"
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func NewCreateTariffWorkflow(
	txManager CreateTariffTxManager,
	parkingRepository parkingdomain.Repository,
	priceLimitRepository tariffdomain.PriceLimitRepository,
	tariffNotifier TariffNotifier,
	publishOutboxEventCommand *outboxdomain.PublishOutboxTariffEventCommand,
) *CreateTariffWorkflow {
	return &CreateTariffWorkflow{
		txManager:                 txManager,
		parkingRepository:         parkingRepository,
		priceLimitRepository:      priceLimitRepository,
		tariffNotifier:            tariffNotifier,
		publishOutboxEventCommand: publishOutboxEventCommand,
	}
}

// CreateTariffWorkflow Создание тарифа на активной парковке в её валюте с ценой в пределах лимита реестра парковок; о новом тарифе узнают партнёрский биллинг и брокер (tariff.created)
type CreateTariffWorkflow struct {
	txManager                 CreateTariffTxManager
	parkingRepository         parkingdomain.Repository
	priceLimitRepository      tariffdomain.PriceLimitRepository
	tariffNotifier            TariffNotifier
	publishOutboxEventCommand *outboxdomain.PublishOutboxTariffEventCommand
}

func (w *CreateTariffWorkflow) Exec(ctx context.Context, params *CreateTariffParams) (*tariffdomain.Tariff, error) {
	// Начальное состояние задаёт сущность: активный, не по умолчанию — флаг ставится отдельным переходом
	tariff := tariffdomain.NewTariff(tariffdomain.Tariff{
		ParkingID:    params.ParkingID,
		Name:         params.Name,
		Currency:     params.Currency,
		GraceMinutes: params.GraceMinutes,
		Price:        params.Price,
	})

	// Инварианты — до обращения к реестру и БД: тот же сценарий вызывается не только из HTTP
	if fieldErrors := tariffdomain.ValidateTariff(tariff); len(fieldErrors) > 0 {
		return nil, exception.NewValidationException(fieldErrors, false)
	}

	// Парковка — своя БД, дешевле внешнего реестра: проверяется первой
	parking, err := w.parkingRepository.GetByID(ctx, params.ParkingID)
	if err != nil {
		return nil, err
	}

	if parking == nil {
		return nil, exception.NewValidationException(map[string]any{"parking_id": "парковка не найдена"}, false)
	}

	// Отключённая парковка — состояние данных, не доступ: 422, как «не найдена»
	if !parking.IsActive {
		return nil, exception.NewValidationException(map[string]any{"parking_id": "парковка отключена"}, false)
	}

	if tariff.Currency != parking.Currency {
		message := fmt.Sprintf("валюта тарифа должна совпадать с валютой парковки (%s)", parking.Currency)

		return nil, exception.NewValidationException(map[string]any{"currency": message}, false)
	}

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

	// Парковку отключили до коммита — тариф останется активным: риск принят
	err = w.txManager.Exec(ctx, func(
		ctx context.Context,
		tariffRepository tariffdomain.Repository,
		outboxRepository outboxdomain.Repository,
	) error {
		saved, createErr := tariffRepository.Create(ctx, tariff)
		if createErr != nil {
			return createErr
		}

		created = saved

		event := outboxdomain.NewTariffCreatedEvent(outboxdomain.CreatedTariff{
			TariffID:     created.ID,
			ParkingID:    created.ParkingID,
			Name:         created.Name,
			Currency:     created.Currency,
			GraceMinutes: created.GraceMinutes,
			Price:        created.Price,
		})

		savedEvent, outboxErr := outboxRepository.Create(ctx, event)
		outboxEvent = savedEvent

		return outboxErr
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
