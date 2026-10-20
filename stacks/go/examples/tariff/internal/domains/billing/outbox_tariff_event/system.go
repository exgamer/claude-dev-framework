package outbox_tariff_event

import "context"

// EventPublisher Публикует событие в RabbitMQ; ошибка — событие не опубликовано, его повторит консольная команда
type EventPublisher interface {
	Publish(ctx context.Context, event *OutboxTariffEvent) error
}
