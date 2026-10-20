package outbox_tariff_event

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
)

func NewRetryOutboxTariffEventsCommand(
	repository Repository,
	publishCommand *PublishOutboxTariffEventCommand,
	settings RetrySettings,
) *RetryOutboxTariffEventsCommand {
	return &RetryOutboxTariffEventsCommand{
		repository:     repository,
		publishCommand: publishCommand,
		settings:       settings,
	}
}

// RetryOutboxTariffEventsCommand Повтор зависших событий (процесс упал, брокер недоступен) пачками, пока выборка полная
type RetryOutboxTariffEventsCommand struct {
	repository     Repository
	publishCommand *PublishOutboxTariffEventCommand
	settings       RetrySettings
}

// Exec Ошибка — только от выборки: сбой отдельного события в трекер, прогон продолжается
func (c *RetryOutboxTariffEventsCommand) Exec(ctx context.Context) (*RetryResult, error) {
	result := &RetryResult{}

	for {
		ids, err := c.repository.ClaimForRetry(ctx, c.settings.BatchSize, c.settings.StaleAfter)
		if err != nil {
			return result, err
		}

		for _, id := range ids {
			c.publish(ctx, id, result)
		}

		// Неполная пачка — зависших событий больше нет
		if uint(len(ids)) < c.settings.BatchSize {
			return result, nil
		}
	}
}

func (c *RetryOutboxTariffEventsCommand) publish(ctx context.Context, id uint, result *RetryResult) {
	if err := c.publishCommand.Exec(ctx, id); err != nil {
		result.Failed++
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "outbox_tariff_event"})

		return
	}

	result.Published++
}
