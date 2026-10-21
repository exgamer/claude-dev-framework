package tariff

import (
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func tariffToEntity(model *tariff) *tariffdomain.Tariff {
	if model == nil {
		return nil
	}

	return tariffdomain.RestoreTariff(tariffdomain.Tariff{
		ID:           model.ID,
		ParkingID:    model.ParkingID,
		Name:         model.Name,
		Currency:     model.Currency,
		GraceMinutes: model.GraceMinutes,
		Price:        model.Price,
	}, model.IsDefault, model.IsActive)
}

func tariffFromEntity(entity *tariffdomain.Tariff) *tariff {
	if entity == nil {
		return nil
	}

	return &tariff{
		ID:           entity.ID,
		ParkingID:    entity.ParkingID,
		Name:         entity.Name,
		Currency:     entity.Currency,
		IsDefault:    entity.IsDefault(),
		IsActive:     entity.IsActive(),
		GraceMinutes: entity.GraceMinutes,
		Price:        entity.Price,
	}
}
