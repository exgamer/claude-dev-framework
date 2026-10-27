package outbox_tariff_event

import (
	"context"
	"fmt"
	"strconv"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// publishTimeout Предел ожидания подтверждения брокера: зависший брокер не держит запрос создания и прогон повтора
const publishTimeout = 5 * time.Second

func NewRabbitPublisher(publisher *rabbitmq.Publisher) *RabbitPublisher {
	return &RabbitPublisher{
		publisher: publisher,
		timeout:   publishTimeout,
	}
}

// RabbitPublisher Публикует событие тарифа с его routing key в exchange издателя из реестра SDK
type RabbitPublisher struct {
	publisher metadataPublisher
	timeout   time.Duration
}

// Publish event_id — в теле и в метаданных, чтобы получатель отсекал повтор, не разбирая тело.
// SDK-публикатор ctx не принимает и с ConfirmDelivery ждёт подтверждения брокера без предела — предел ставит адаптер
func (p *RabbitPublisher) Publish(ctx context.Context, event *outboxdomain.OutboxTariffEvent) error {
	ctx, cancel := context.WithTimeout(ctx, p.timeout)
	defer cancel()

	metadata := map[string]string{"event_id": strconv.FormatUint(uint64(event.ID), 10)}
	message := entityToMessage(event)

	// Буфер на один результат: после таймаута горутина завершится сама, когда SDK вернёт управление
	done := make(chan error, 1)

	go func() {
		defer func() {
			if recovered := recover(); recovered != nil {
				done <- fmt.Errorf("publisher panic: %v", recovered)
			}
		}()

		done <- p.publisher.PublishWithMetaData(event.RoutingKey, metadata, message)
	}()

	select {
	case err := <-done:
		if err != nil {
			return fmt.Errorf("publish tariff event failed (outbox_id=%d): %w", event.ID, err)
		}

		return nil
	case <-ctx.Done():
		// Подтверждение могло прийти позже — событие уйдёт повтором ещё раз, получатель отсекает дубль по event_id
		return fmt.Errorf("publish tariff event failed (outbox_id=%d): %w", event.ID, ctx.Err())
	}
}
