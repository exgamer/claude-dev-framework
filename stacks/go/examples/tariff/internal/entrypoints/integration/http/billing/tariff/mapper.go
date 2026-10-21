package tariff

import (
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func tariffToData(tariff *tariffdomain.Tariff) *tariffData {
	if tariff == nil {
		return nil
	}

	return &tariffData{
		ID:           tariff.ID,
		ParkingID:    tariff.ParkingID,
		Currency:     string(tariff.Currency),
		IsActive:     tariff.IsActive(),
		GraceMinutes: tariff.GraceMinutes,
		Price:        tariff.Price,
	}
}
