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

// SetDefaultTariffCommand Переход «тариф по умолчанию»; прежний тариф по умолчанию той же парковки теряет флаг
type SetDefaultTariffCommand struct {
	cacheRepository CacheRepository
	txManager       DefaultTariffTxManager
}

// Exec Чужой или удалённый тариф — 404; допустимость перехода — у сущности (CanMakeDefault, раньше проверки «уже по умолчанию»), отказ — 422.
// Поиск прежнего и снятие его флага — для обычного случая; от гонки защищают условный UPDATE (0 строк — 422) и уникальный индекс (ErrDefaultTariffTaken — 422), транзакция откатывается
func (c *SetDefaultTariffCommand) Exec(ctx context.Context, id uint, scope ParkingScope) error {
	var changedIDs []uint

	err := c.txManager.Exec(ctx, func(ctx context.Context, repository Repository) error {
		tariff, err := repository.GetByID(ctx, id, scope)
		if err != nil {
			return err
		}

		if tariff == nil {
			return exception.NewNotFoundException(errors.New("тариф не найден"), false)
		}

		// Допустимость — раньше «уже по умолчанию»: отключение парковки флаг не снимает, отключённый с флагом — тоже 422
		if err := tariff.CanMakeDefault(); err != nil {
			return err
		}

		// Повтор запроса безопасен: тариф уже по умолчанию — записи нет
		if tariff.IsDefault() {
			return nil
		}

		if err := tariff.MakeDefault(); err != nil {
			return err
		}

		previous, err := repository.GetDefaultByParkingID(ctx, tariff.ParkingID)
		if err != nil {
			return err
		}

		if previous != nil {
			previous.UnsetDefault()

			// Прежний удалён или снят параллельно — снимать нечего; второй флаг не даст поставить уникальный индекс
			if err := repository.UnsetDefault(ctx, previous.ID); err != nil && !errors.Is(err, ErrTariffStateChanged) {
				return err
			}

			changedIDs = append(changedIDs, previous.ID)
		}

		changedIDs = append(changedIDs, tariff.ID)

		return repository.MakeDefault(ctx, tariff.ID)
	})
	if errors.Is(err, ErrTariffInactive) {
		return exception.NewValidationException(map[string]any{"id": "отключённый тариф нельзя сделать тарифом по умолчанию"}, false)
	}

	if errors.Is(err, ErrDefaultTariffTaken) {
		return exception.NewValidationException(map[string]any{"is_default": "тариф по умолчанию этой парковки только что изменён другим запросом, обновите данные и повторите"}, false)
	}

	// Тариф удалён, отключён или назначен между чтением и записью — условный UPDATE изменил 0 строк
	if errors.Is(err, ErrTariffStateChanged) {
		return exception.NewValidationException(map[string]any{"id": "состояние тарифа только что изменено другим запросом, обновите данные и повторите"}, false)
	}

	if err != nil {
		return err
	}

	if err := c.cacheRepository.Invalidate(ctx, changedIDs...); err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "tariff_cache"})
	}

	return nil
}
