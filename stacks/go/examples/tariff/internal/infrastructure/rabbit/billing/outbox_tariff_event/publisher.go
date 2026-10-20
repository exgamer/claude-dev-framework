package outbox_tariff_event

import (
	"context"
	"fmt"
	"strconv"

	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

func NewRabbitPublisher(publisher *rabbitmq.Publisher) *RabbitPublisher {
	return &RabbitPublisher{
		publisher: publisher,
	}
}

// RabbitPublisher Публикует событие тарифа с его routing key в exchange издателя из реестра SDK
type RabbitPublisher struct {
	publisher *rabbitmq.Publisher
}

// Publish event_id — в теле и в метаданных, чтобы получатель отсекал повтор, не разбирая тело.
// ctx SDK-публикатор не принимает; с ConfirmDelivery издателя Publish ждёт подтверждения брокера
func (p *RabbitPublisher) Publish(_ context.Context, event *outboxdomain.OutboxTariffEvent) error {
	metadata := map[string]string{"event_id": strconv.FormatUint(uint64(event.ID), 10)}

	if err := p.publisher.PublishWithMetaData(event.RoutingKey, metadata, entityToMessage(event)); err != nil {
		return fmt.Errorf("publish tariff event failed (outbox_id=%d): %w", event.ID, err)
	}

	return nil
}
