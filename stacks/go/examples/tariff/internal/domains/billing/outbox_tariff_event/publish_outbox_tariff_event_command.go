package outbox_tariff_event

import (
	"context"
	"fmt"
)

func NewPublishOutboxTariffEventCommand(
	repository Repository,
	publisher EventPublisher,
	txManager FailedAttemptTxManager,
	maxAttempts uint,
) *PublishOutboxTariffEventCommand {
	return &PublishOutboxTariffEventCommand{
		repository:  repository,
		publisher:   publisher,
		txManager:   txManager,
		maxAttempts: maxAttempts,
	}
}

// PublishOutboxTariffEventCommand Отправка одного outbox-события в RabbitMQ с учётом попыток; опубликованное и исчерпавшее попытки не отправляет
type PublishOutboxTariffEventCommand struct {
	repository  Repository
	publisher   EventPublisher
	txManager   FailedAttemptTxManager
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

	attempts, err := c.recordFailedAttempt(ctx, outboxID)
	if err != nil {
		return err
	}

	if attempts >= c.maxAttempts {
		return fmt.Errorf("outbox tariff event failed after %d attempts (outbox_id=%d): %w", attempts, outboxID, publishErr)
	}

	return fmt.Errorf("publish outbox tariff event failed, awaiting retry (outbox_id=%d, attempts=%d): %w", outboxID, attempts, publishErr)
}

// recordFailedAttempt Попытка и статус — вместе: счётчик без статуса дал бы StatusFailed по числу, но StatusAwaitingRetry в выборке повтора
func (c *PublishOutboxTariffEventCommand) recordFailedAttempt(ctx context.Context, outboxID uint) (uint, error) {
	var attempts uint

	err := c.txManager.Exec(ctx, func(ctx context.Context, repository Repository) error {
		incremented, incrementErr := repository.IncrementAttempts(ctx, outboxID)
		if incrementErr != nil {
			return fmt.Errorf("increment outbox tariff event attempts failed (outbox_id=%d): %w", outboxID, incrementErr)
		}

		attempts = incremented

		if attempts >= c.maxAttempts {
			return repository.MarkFailed(ctx, outboxID)
		}

		return repository.MarkAwaitingRetry(ctx, outboxID)
	})

	return attempts, err
}
