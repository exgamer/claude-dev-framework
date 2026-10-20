package parking

import (
	"example.com/parking-service/internal/common"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func modelToEntity(model *parking) *parkingdomain.Parking {
	if model == nil {
		return nil
	}

	return &parkingdomain.Parking{
		ID:       model.ID,
		LPMHost:  model.LPMHost,
		IsActive: model.IsActive,
		Currency: common.Currency(model.Currency),
	}
}
