package tariff_test

import (
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func TestNewTariff_ActiveAndNotDefault(t *testing.T) {
	tariff := tariffdomain.NewTariff(tariffdomain.Tariff{ParkingID: 7, Name: "Стандарт"})

	assert.True(t, tariff.IsActive())
	assert.False(t, tariff.IsDefault())
}

func TestTariff_MakeDefault(t *testing.T) {
	tariff := activeTariff(1, 7)

	require.NoError(t, tariff.MakeDefault())
	assert.True(t, tariff.IsDefault())
}

func TestTariff_MakeDefault_InactiveIsRejected(t *testing.T) {
	tariff := tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, false)

	require.ErrorIs(t, tariff.CanMakeDefault(), tariffdomain.ErrTariffInactive)
	require.ErrorIs(t, tariff.MakeDefault(), tariffdomain.ErrTariffInactive)
	assert.False(t, tariff.IsDefault(), "недопустимый переход состояние не меняет")
}

func TestTariff_UnsetDefault(t *testing.T) {
	tariff := defaultTariff(1, 7)

	tariff.UnsetDefault()

	assert.False(t, tariff.IsDefault())
}

func TestTariff_CanDeactivate(t *testing.T) {
	require.NoError(t, tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1}, false, true).CanDeactivate())
	require.ErrorIs(t, tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 2}, false, false).CanDeactivate(), tariffdomain.ErrTariffInactive)
}
