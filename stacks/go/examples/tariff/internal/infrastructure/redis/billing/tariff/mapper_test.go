package tariff

import (
	"encoding/json"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// TestMapper_StateSurvivesCache Состояние сущности неэкспортируемое: через модель оно переживает JSON кеша
func TestMapper_StateSurvivesCache(t *testing.T) {
	entity := tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7, Name: "Стандарт"}, true, true)

	payload, err := json.Marshal(tariffFromEntity(entity))
	require.NoError(t, err)

	var model tariff
	require.NoError(t, json.Unmarshal(payload, &model))

	restored := tariffToEntity(&model)
	assert.Equal(t, entity, restored)
	assert.True(t, restored.IsDefault())
	assert.True(t, restored.IsActive())
}
