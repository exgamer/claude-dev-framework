package tariff

import (
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
		Currency:     model.Currency,
		IsDefault:    model.IsDefault,
		GraceMinutes: model.GraceMinutes,
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
		Currency:     entity.Currency,
		IsDefault:    entity.IsDefault,
		GraceMinutes: entity.GraceMinutes,
	}
}

// patchToColumns Карта колонок строится только здесь: домен передаёт типизированный Patch
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
