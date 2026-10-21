package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
)

func NewGetDefaultTariffQuery(repository Repository, cacheRepository CacheRepository) *GetDefaultTariffQuery {
	return &GetDefaultTariffQuery{
		repository:      repository,
		cacheRepository: cacheRepository,
	}
}

// GetDefaultTariffQuery Действующий тариф по умолчанию парковки — тот, что применяется к новой стоянке
type GetDefaultTariffQuery struct {
	repository      Repository
	cacheRepository CacheRepository
}

// Exec Парковка вне скоупа (все — только при All), тарифа по умолчанию нет или он отключён — 404.
// Найденный тариф кладётся в кеш по ID: следующим шагом клиент обычно читает его через GetByID
func (q *GetDefaultTariffQuery) Exec(ctx context.Context, parkingID uint, scope ParkingScope) (*Tariff, error) {
	if !scope.Allows(parkingID) {
		return nil, exception.NewNotFoundException(errors.New("тариф по умолчанию не найден"), false)
	}

	tariff, err := q.repository.GetDefaultByParkingID(ctx, parkingID)
	if err != nil {
		return nil, err
	}

	if tariff == nil || !tariff.IsActive() {
		return nil, exception.NewNotFoundException(errors.New("тариф по умолчанию не найден"), false)
	}

	if err := q.cacheRepository.Set(ctx, tariff); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	return tariff, nil
}
