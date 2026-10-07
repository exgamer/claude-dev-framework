package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func NewCreateTariffWorkflow(txManager CreateTariffTxManager) *CreateTariffWorkflow {
	return &CreateTariffWorkflow{
		txManager: txManager,
	}
}

// CreateTariffWorkflow Создание тарифа с проверкой парковки: billing не знает о handbook, связь живёт здесь
type CreateTariffWorkflow struct {
	txManager CreateTariffTxManager
}

func (w *CreateTariffWorkflow) Exec(ctx context.Context, params *CreateTariffParams) (*tariffdomain.Tariff, error) {
	var created *tariffdomain.Tariff

	err := w.txManager.Exec(ctx, func(ctx context.Context, parkingRepository parkingdomain.Repository, tariffRepository tariffdomain.Repository) error {
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

		// Новый тариф не дефолтный: дефолт назначается через SetDefaultTariffCommand
		created, err = tariffRepository.Create(ctx, &tariffdomain.Tariff{
			ParkingID:    params.ParkingID,
			Name:         params.Name,
			Currency:     params.Currency,
			GraceMinutes: params.GraceMinutes,
		})

		return err
	})
	if err != nil {
		return nil, err
	}

	return created, nil
}
