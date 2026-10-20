package pricelimits

import (
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func priceLimitToEntity(model *priceLimit) *tariffdomain.PriceLimit {
	if model == nil {
		return nil
	}

	return &tariffdomain.PriceLimit{
		ParkingID: model.ParkingID,
		MaxPrice:  model.MaxPrice,
	}
}
