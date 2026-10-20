package tariffs

import (
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func tariffFromEntity(entity *tariffdomain.Tariff) *tariff {
	if entity == nil {
		return nil
	}

	return &tariff{
		ID:        entity.ID,
		ParkingID: entity.ParkingID,
		Name:      entity.Name,
		Currency:  entity.Currency,
		Price:     entity.Price,
	}
}
