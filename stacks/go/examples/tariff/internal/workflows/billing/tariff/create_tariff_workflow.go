package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func NewCreateTariffWorkflow(
	parkingRepository parkingdomain.Repository,
	tariffService *tariffdomain.Service,
) *CreateTariffWorkflow {
	return &CreateTariffWorkflow{
		parkingRepository: parkingRepository,
		tariffService:     tariffService,
	}
}

// CreateTariffWorkflow Создание тарифа с проверкой парковки: billing не знает о handbook, связь живёт здесь
type CreateTariffWorkflow struct {
	parkingRepository parkingdomain.Repository
	tariffService     *tariffdomain.Service
}

func (w *CreateTariffWorkflow) Exec(ctx context.Context, params *CreateTariffParams) (*tariffdomain.Tariff, error) {
	parking, err := w.parkingRepository.GetByID(ctx, params.ParkingID)
	if err != nil {
		return nil, err
	}

	if parking == nil {
		return nil, exception.NewValidationException(map[string]any{"parking_id": "парковка не найдена"}, false)
	}

	if !parking.IsActive {
		return nil, exception.NewForbiddenException(errors.New("парковка отключена"), false)
	}

	return w.tariffService.Create(ctx, &tariffdomain.Tariff{
		ParkingID:    params.ParkingID,
		Name:         params.Name,
		Currency:     params.Currency,
		GraceMinutes: params.GraceMinutes,
	})
}
