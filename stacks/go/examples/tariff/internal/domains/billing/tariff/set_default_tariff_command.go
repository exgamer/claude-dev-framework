package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
)

func NewSetDefaultTariffCommand(cacheRepository CacheRepository, txManager DefaultTariffTxManager) *SetDefaultTariffCommand {
	return &SetDefaultTariffCommand{
		cacheRepository: cacheRepository,
		txManager:       txManager,
	}
}

// SetDefaultTariffCommand Назначение тарифа по умолчанию; прежний тариф по умолчанию той же парковки теряет флаг
type SetDefaultTariffCommand struct {
	cacheRepository CacheRepository
	txManager       DefaultTariffTxManager
}

// Exec Тариф ищется в пределах парковок parkingIDs (nil — без ограничения); чужой — 404.
// Поиск прежнего и снятие его флага — для обычного случая; от параллельной смены защищает уникальный индекс: ErrDefaultTariffTaken → 422, транзакция откатывается
func (c *SetDefaultTariffCommand) Exec(ctx context.Context, id uint, parkingIDs []uint) error {
	var changedIDs []uint

	err := c.txManager.Exec(ctx, func(ctx context.Context, repository Repository) error {
		tariff, err := repository.GetByID(ctx, id, parkingIDs)
		if err != nil {
			return err
		}

		if tariff == nil {
			return exception.NewNotFoundException(errors.New("тариф не найден"), false)
		}

		if tariff.IsDefault {
			return nil
		}

		previous, err := repository.GetDefaultByParkingID(ctx, tariff.ParkingID)
		if err != nil {
			return err
		}

		if previous != nil {
			if err := repository.SetDefault(ctx, previous.ID, false); err != nil {
				return err
			}

			changedIDs = append(changedIDs, previous.ID)
		}

		changedIDs = append(changedIDs, tariff.ID)

		return repository.SetDefault(ctx, tariff.ID, true)
	})
	if errors.Is(err, ErrDefaultTariffTaken) {
		return exception.NewValidationException(map[string]any{"is_default": "тариф по умолчанию этой парковки только что изменён другим запросом, обновите данные и повторите"}, false)
	}

	if err != nil {
		return err
	}

	for _, changedID := range changedIDs {
		c.invalidate(ctx, changedID)
	}

	return nil
}

func (c *SetDefaultTariffCommand) invalidate(ctx context.Context, id uint) {
	if err := c.cacheRepository.Invalidate(ctx, id); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}
}
