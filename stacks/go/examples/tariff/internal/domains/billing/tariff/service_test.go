package tariff_test

import (
	"context"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

func TestService_DeactivateByParkingID(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsActive: true},
		&tariffdomain.Tariff{ID: 2, ParkingID: 7, IsActive: true},
		&tariffdomain.Tariff{ID: 3, ParkingID: 8, IsActive: true},
	)
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	cache.Items[3] = *tariffs.Items[3]
	service := tariffdomain.NewService(tariffs, cache)

	err := service.DeactivateByParkingID(context.Background(), 7)

	require.NoError(t, err)
	assert.False(t, tariffs.Items[1].IsActive)
	assert.False(t, tariffs.Items[2].IsActive)
	assert.True(t, tariffs.Items[3].IsActive, "тариф другой парковки не трогается")
	assert.NotContains(t, cache.Items, uint(1), "кеш отключённого тарифа сброшен")
	assert.Contains(t, cache.Items, uint(3))
}

func TestService_DeactivateByParkingID_RepeatIsNoop(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsActive: false})
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	service := tariffdomain.NewService(tariffs, cache)

	err := service.DeactivateByParkingID(context.Background(), 7)

	require.NoError(t, err)
	assert.False(t, tariffs.Items[1].IsActive)
	assert.Contains(t, cache.Items, uint(1), "повтор события ничего не меняет")
}

func newScopedService() (*tariffdomain.Service, *billingfakes.TariffRepository, *billingfakes.TariffCacheRepository) {
	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7, Name: "свой"},
		&tariffdomain.Tariff{ID: 2, ParkingID: 8, Name: "чужой"},
	)
	cache := billingfakes.NewTariffCacheRepository()

	return tariffdomain.NewService(tariffs, cache), tariffs, cache
}

func requireNotFound(t *testing.T, err error) {
	t.Helper()

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindNotFound, appException.Kind)
}

func TestService_GetByID_Scope(t *testing.T) {
	service, _, _ := newScopedService()

	own, err := service.GetByID(context.Background(), 1, tariffdomain.ParkingScope{IDs: []uint{7}})
	require.NoError(t, err)
	assert.Equal(t, uint(1), own.ID)

	_, err = service.GetByID(context.Background(), 2, tariffdomain.ParkingScope{IDs: []uint{7}})
	requireNotFound(t, err)

	_, err = service.GetByID(context.Background(), 1, tariffdomain.ParkingScope{})
	requireNotFound(t, err)
}

func TestService_GetByID_AllSeesAll(t *testing.T) {
	service, _, _ := newScopedService()

	for _, id := range []uint{1, 2} {
		tariff, err := service.GetByID(context.Background(), id, tariffdomain.ParkingScope{All: true})
		require.NoError(t, err)
		assert.Equal(t, id, tariff.ID)
	}
}

func TestService_GetByID_CachedOtherParkingIsNotFound(t *testing.T) {
	service, tariffs, cache := newScopedService()
	cache.Items[2] = *tariffs.Items[2]

	_, err := service.GetByID(context.Background(), 2, tariffdomain.ParkingScope{IDs: []uint{7}})

	requireNotFound(t, err)
}

func TestService_Update_OtherParkingIsNotFound(t *testing.T) {
	service, tariffs, _ := newScopedService()
	name := "переименован"

	_, err := service.Update(context.Background(), 2, tariffdomain.ParkingScope{IDs: []uint{7}}, &tariffdomain.Patch{Name: &name})

	requireNotFound(t, err)
	assert.Equal(t, "чужой", tariffs.Items[2].Name, "чужой тариф не изменён")
}

func TestService_Paginated_Scope(t *testing.T) {
	service, _, _ := newScopedService()

	scoped, err := service.Paginated(context.Background(), &tariffdomain.Search{Scope: tariffdomain.ParkingScope{IDs: []uint{7}}})
	require.NoError(t, err)
	require.Len(t, scoped.Items, 1)
	assert.Equal(t, uint(1), scoped.Items[0].ID)

	all, err := service.Paginated(context.Background(), &tariffdomain.Search{Scope: tariffdomain.ParkingScope{All: true}})
	require.NoError(t, err)
	assert.Len(t, all.Items, 2)

	none, err := service.Paginated(context.Background(), &tariffdomain.Search{})
	require.NoError(t, err)
	assert.Empty(t, none.Items)
}

func TestService_Update_InvalidPatchIs422(t *testing.T) {
	service, tariffs, _ := newScopedService()
	blank := "  "

	_, err := service.Update(context.Background(), 1, tariffdomain.ParkingScope{All: true}, &tariffdomain.Patch{Name: &blank})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "name")
	assert.Equal(t, "свой", tariffs.Items[1].Name, "тариф не изменён")
}

// TestService_Update_DeletedBetweenReadAndWriteIsNotFound Тариф прочитан (здесь — из кеша), но запись изменила 0 строк — 404
func TestService_Update_DeletedBetweenReadAndWriteIsNotFound(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository()
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[5] = tariffdomain.Tariff{ID: 5, ParkingID: 7, Name: "удалён"}
	service := tariffdomain.NewService(tariffs, cache)
	name := "переименован"

	_, err := service.Update(context.Background(), 5, tariffdomain.ParkingScope{All: true}, &tariffdomain.Patch{Name: &name})

	requireNotFound(t, err)
}
