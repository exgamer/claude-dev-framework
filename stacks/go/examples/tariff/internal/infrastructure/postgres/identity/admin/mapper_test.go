package admin

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestModelToEntity_DecodesSystemRoles(t *testing.T) {
	entity, err := modelToEntity(&admin{ID: 1, SystemRoles: []byte(`["super-admin"]`)})

	require.NoError(t, err)
	assert.Equal(t, []string{"super-admin"}, entity.SystemRoles)
}

func TestModelToEntity_NullRolesAreEmpty(t *testing.T) {
	entity, err := modelToEntity(&admin{ID: 1})

	require.NoError(t, err)
	assert.NotNil(t, entity.SystemRoles)
	assert.Empty(t, entity.SystemRoles)
}

func TestModelToEntity_BrokenRolesIsError(t *testing.T) {
	_, err := modelToEntity(&admin{ID: 1, SystemRoles: []byte(`{`)})

	assert.ErrorContains(t, err, "admin_id=1")
}
