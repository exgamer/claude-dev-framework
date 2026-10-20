package outbox_tariff_event

import (
	"example.com/parking-service/internal/common"
	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

func modelToEntity(model *outboxTariffEvent) *outboxdomain.OutboxTariffEvent {
	if model == nil {
		return nil
	}

	return &outboxdomain.OutboxTariffEvent{
		ID:           model.ID,
		TariffID:     model.TariffID,
		ParkingID:    model.ParkingID,
		Name:         model.Name,
		Currency:     common.Currency(model.Currency),
		GraceMinutes: model.GraceMinutes,
		Price:        model.Price,
		RoutingKey:   model.RoutingKey,
		Status:       outboxdomain.Status(model.Status),
		Attempts:     model.Attempts,
		PublishedAt:  model.PublishedAt,
	}
}

func entityToModel(entity *outboxdomain.OutboxTariffEvent) *outboxTariffEvent {
	if entity == nil {
		return nil
	}

	return &outboxTariffEvent{
		ID:           entity.ID,
		TariffID:     entity.TariffID,
		ParkingID:    entity.ParkingID,
		Name:         entity.Name,
		Currency:     string(entity.Currency),
		GraceMinutes: entity.GraceMinutes,
		Price:        entity.Price,
		RoutingKey:   entity.RoutingKey,
		Status:       uint(entity.Status),
		Attempts:     entity.Attempts,
		PublishedAt:  entity.PublishedAt,
	}
}
