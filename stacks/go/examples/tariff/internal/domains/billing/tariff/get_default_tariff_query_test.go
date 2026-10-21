package tariff_test

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

func newDefaultTariffQuery(items ...*tariffdomain.Tariff) (*tariffdomain.GetDefaultTariffQuery, *billingfakes.TariffCacheRepository) {
	cache := billingfakes.NewTariffCacheRepository()

	return tariffdomain.NewGetDefaultTariffQuery(billingfakes.NewTariffRepository(items...), cache), cache
}

func TestGetDefaultTariffQuery_ReturnsActiveDefaultAndCachesIt(t *testing.T) {
	query, cache := newDefaultTariffQuery(
		tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, true),
		tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 2, ParkingID: 7}, true, true),
		tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 3, ParkingID: 8}, true, true),
	)

	tariff, err := query.Exec(context.Background(), 7, tariffdomain.ParkingScope{IDs: []uint{7}})

	require.NoError(t, err)
	assert.Equal(t, uint(2), tariff.ID)
	assert.Contains(t, cache.Items, uint(2))
}

func TestGetDefaultTariffQuery_NotFound(t *testing.T) {
	cases := []struct {
		name    string
		tariffs []*tariffdomain.Tariff
		scope   tariffdomain.ParkingScope
	}{
		{"no default", []*tariffdomain.Tariff{tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, true)}, tariffdomain.ParkingScope{All: true}},
		{"default is inactive", []*tariffdomain.Tariff{tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, true, false)}, tariffdomain.ParkingScope{All: true}},
		{"parking out of scope", []*tariffdomain.Tariff{tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, true, true)}, tariffdomain.ParkingScope{IDs: []uint{8}}},
		{"zero scope", []*tariffdomain.Tariff{tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, true, true)}, tariffdomain.ParkingScope{}},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			query, cache := newDefaultTariffQuery(tc.tariffs...)

			_, err := query.Exec(context.Background(), 7, tc.scope)

			requireNotFound(t, err)
			assert.Empty(t, cache.Items)
		})
	}
}
