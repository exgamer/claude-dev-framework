package tariff

import (
	"example.com/parking-service/internal/common"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func tariffToEntity(model *tariff) *tariffdomain.Tariff {
	if model == nil {
		return nil
	}

	return &tariffdomain.Tariff{
		ID:           model.ID,
		ParkingID:    model.ParkingID,
		Name:         model.Name,
		Currency:     common.Currency(model.Currency),
		IsDefault:    model.IsDefault,
		IsActive:     model.IsActive,
		GraceMinutes: model.GraceMinutes,
		Price:        model.Price,
	}
}

func tariffFromEntity(entity *tariffdomain.Tariff) *tariff {
	if entity == nil {
		return nil
	}

	return &tariff{
		ID:           entity.ID,
		ParkingID:    entity.ParkingID,
		Name:         entity.Name,
		Currency:     string(entity.Currency),
		IsDefault:    entity.IsDefault,
		IsActive:     entity.IsActive,
		GraceMinutes: entity.GraceMinutes,
		Price:        entity.Price,
	}
}

func patchToColumns(patch *tariffdomain.Patch) map[string]any {
	columns := map[string]any{}

	if patch == nil {
		return columns
	}

	if patch.Name != nil {
		columns["name"] = *patch.Name
	}

	if patch.GraceMinutes != nil {
		columns["grace_minutes"] = *patch.GraceMinutes
	}

	return columns
}
