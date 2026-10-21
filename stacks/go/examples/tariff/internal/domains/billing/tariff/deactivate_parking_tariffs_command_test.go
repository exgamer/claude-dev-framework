package tariff_test

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

func activeTariff(id, parkingID uint) *tariffdomain.Tariff {
	return tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: id, ParkingID: parkingID}, false, true)
}

func TestDeactivateParkingTariffsCommand_DeactivatesAndInvalidates(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(activeTariff(1, 7), activeTariff(2, 7), activeTariff(3, 8))
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	cache.Items[3] = *tariffs.Items[3]
	command := tariffdomain.NewDeactivateParkingTariffsCommand(tariffs, cache)

	err := command.Exec(context.Background(), 7)

	require.NoError(t, err)
	assert.False(t, tariffs.Items[1].IsActive())
	assert.False(t, tariffs.Items[2].IsActive())
	assert.True(t, tariffs.Items[3].IsActive(), "тариф другой парковки не трогается")
	assert.NotContains(t, cache.Items, uint(1), "кеш отключённого тарифа сброшен")
	assert.Contains(t, cache.Items, uint(3))
}

func TestDeactivateParkingTariffsCommand_RepeatIsNoop(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, false))
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	command := tariffdomain.NewDeactivateParkingTariffsCommand(tariffs, cache)

	err := command.Exec(context.Background(), 7)

	require.NoError(t, err)
	assert.False(t, tariffs.Items[1].IsActive())
	assert.Contains(t, cache.Items, uint(1), "повтор события ничего не меняет")
}
