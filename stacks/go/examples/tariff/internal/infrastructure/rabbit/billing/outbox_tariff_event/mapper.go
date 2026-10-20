package outbox_tariff_event

import (
	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

func entityToMessage(entity *outboxdomain.OutboxTariffEvent) *eventMessage {
	if entity == nil {
		return nil
	}

	return &eventMessage{
		EventID:      entity.ID,
		TariffID:     entity.TariffID,
		ParkingID:    entity.ParkingID,
		Name:         entity.Name,
		Currency:     entity.Currency,
		GraceMinutes: entity.GraceMinutes,
		Price:        entity.Price,
	}
}
