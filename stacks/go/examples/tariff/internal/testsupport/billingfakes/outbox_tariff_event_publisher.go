package billingfakes

import (
	"context"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// OutboxTariffEventPublisher In-memory outboxdomain.EventPublisher: запоминает опубликованные события, Err — ошибка каждой публикации
type OutboxTariffEventPublisher struct {
	Err       error
	Published []*outboxdomain.OutboxTariffEvent
	Calls     int
}

func (p *OutboxTariffEventPublisher) Publish(_ context.Context, event *outboxdomain.OutboxTariffEvent) error {
	p.Calls++

	if p.Err != nil {
		return p.Err
	}

	p.Published = append(p.Published, event)

	return nil
}
