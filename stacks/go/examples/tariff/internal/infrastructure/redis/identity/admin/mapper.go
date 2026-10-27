package admin

import (
	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

func adminToEntity(model *admin) *admindomain.Admin {
	if model == nil {
		return nil
	}

	return &admindomain.Admin{
		ID:                model.ID,
		SystemRoles:       model.SystemRoles,
		Permissions:       model.Permissions,
		AllowedParkingIDs: model.AllowedParkingIDs,
		RevokedAt:         model.RevokedAt,
	}
}

func adminFromEntity(entity *admindomain.Admin) *admin {
	if entity == nil {
		return nil
	}

	return &admin{
		ID:                entity.ID,
		SystemRoles:       entity.SystemRoles,
		Permissions:       entity.Permissions,
		AllowedParkingIDs: entity.AllowedParkingIDs,
		RevokedAt:         entity.RevokedAt,
	}
}
