package outbox_tariff_event

import (
	"context"
	"fmt"
)

func NewPublishOutboxTariffEventCommand(repository Repository, publisher EventPublisher, maxAttempts uint) *PublishOutboxTariffEventCommand {
	return &PublishOutboxTariffEventCommand{
		repository:  repository,
		publisher:   publisher,
		maxAttempts: maxAttempts,
	}
}

// PublishOutboxTariffEventCommand Отправка одного outbox-события в RabbitMQ с учётом попыток; опубликованное и исчерпавшее попытки не отправляет
type PublishOutboxTariffEventCommand struct {
	repository  Repository
	publisher   EventPublisher
	maxAttempts uint
}

// Exec Ошибка — событие не опубликовано (статус и попытки уже записаны) или недоступно хранилище
func (c *PublishOutboxTariffEventCommand) Exec(ctx context.Context, outboxID uint) error {
	event, err := c.repository.FindByID(ctx, outboxID)
	if err != nil {
		return fmt.Errorf("find outbox tariff event failed (outbox_id=%d): %w", outboxID, err)
	}

	if event == nil || !event.IsDeliverable() {
		return nil
	}

	// Упали между публикацией и MarkPublished — событие уйдёт ещё раз, получатель отсекает дубль по event_id
	publishErr := c.publisher.Publish(ctx, event)
	if publishErr == nil {
		return c.repository.MarkPublished(ctx, outboxID)
	}

	attempts, err := c.repository.IncrementAttempts(ctx, outboxID)
	if err != nil {
		return fmt.Errorf("increment outbox tariff event attempts failed (outbox_id=%d): %w", outboxID, err)
	}

	if attempts >= c.maxAttempts {
		if err := c.repository.MarkFailed(ctx, outboxID); err != nil {
			return err
		}

		return fmt.Errorf("outbox tariff event failed after %d attempts (outbox_id=%d): %w", attempts, outboxID, publishErr)
	}

	if err := c.repository.MarkAwaitingRetry(ctx, outboxID); err != nil {
		return err
	}

	return fmt.Errorf("publish outbox tariff event failed, awaiting retry (outbox_id=%d, attempts=%d): %w", outboxID, attempts, publishErr)
}
