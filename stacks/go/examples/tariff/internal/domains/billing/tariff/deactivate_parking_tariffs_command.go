package tariff

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
)

func NewDeactivateParkingTariffsCommand(repository Repository, cacheRepository CacheRepository) *DeactivateParkingTariffsCommand {
	return &DeactivateParkingTariffsCommand{
		repository:      repository,
		cacheRepository: cacheRepository,
	}
}

// DeactivateParkingTariffsCommand Переход «отключён» для тарифов отключённой парковки
type DeactivateParkingTariffsCommand struct {
	repository      Repository
	cacheRepository CacheRepository
}

// Exec Повтор безопасен: условный UPDATE не выбирает уже неактивные тарифы; кеш сбрасывается по ID изменённых
func (c *DeactivateParkingTariffsCommand) Exec(ctx context.Context, parkingID uint) error {
	ids, err := c.repository.DeactivateByParkingID(ctx, parkingID)
	if err != nil {
		return err
	}

	if err := c.cacheRepository.Invalidate(ctx, ids...); err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "tariff_cache"})
	}

	return nil
}
