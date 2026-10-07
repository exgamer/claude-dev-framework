package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
)

func NewSetDefaultTariffCommand(
	repository Repository,
	cacheRepository CacheRepository,
	txManager DefaultTariffTxManager,
) *SetDefaultTariffCommand {
	return &SetDefaultTariffCommand{
		repository:      repository,
		cacheRepository: cacheRepository,
		txManager:       txManager,
	}
}

// SetDefaultTariffCommand Назначение тарифа по умолчанию. Вынесен из Service: две записи в транзакции и сброс кеша обоих тарифов
type SetDefaultTariffCommand struct {
	repository      Repository
	cacheRepository CacheRepository
	txManager       DefaultTariffTxManager
}

func (c *SetDefaultTariffCommand) Exec(ctx context.Context, id uint) error {
	tariff, err := c.repository.GetByID(ctx, id)
	if err != nil {
		return err
	}

	if tariff == nil {
		return exception.NewNotFoundException(errors.New("тариф не найден"), false)
	}

	if tariff.IsDefault {
		return nil
	}

	var previousID uint

	err = c.txManager.Exec(ctx, func(ctx context.Context, repository Repository) error {
		previous, err := repository.GetDefaultByParkingID(ctx, tariff.ParkingID)
		if err != nil {
			return err
		}

		if previous != nil {
			previousID = previous.ID

			if err := repository.SetDefault(ctx, previous.ID, false); err != nil {
				return err
			}
		}

		return repository.SetDefault(ctx, tariff.ID, true)
	})
	if err != nil {
		return err
	}

	c.invalidate(ctx, tariff.ID)

	if previousID != 0 {
		c.invalidate(ctx, previousID)
	}

	return nil
}

func (c *SetDefaultTariffCommand) invalidate(ctx context.Context, id uint) {
	if err := c.cacheRepository.Invalidate(ctx, id); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}
}
