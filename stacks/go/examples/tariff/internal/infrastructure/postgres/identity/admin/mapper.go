package admin

import (
	"encoding/json"
	"fmt"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

func modelToEntity(model *admin) (*admindomain.Admin, error) {
	if model == nil {
		return nil, nil
	}

	roles := []string{}
	if len(model.SystemRoles) > 0 {
		if err := json.Unmarshal(model.SystemRoles, &roles); err != nil {
			return nil, fmt.Errorf("decode admin system roles failed (admin_id=%d): %w", model.ID, err)
		}
	}

	return &admindomain.Admin{
		ID:          model.ID,
		SystemRoles: roles,
		RevokedAt:   model.RevokedAt,
	}, nil
}
