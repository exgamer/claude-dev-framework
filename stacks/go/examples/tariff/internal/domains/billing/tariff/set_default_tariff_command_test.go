package tariff_test

import (
	"context"
	"fmt"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
	"example.com/parking-service/internal/testsupport/txfakes"
)

func newSetDefaultCommand(tariffs *billingfakes.TariffRepository, cache *billingfakes.TariffCacheRepository) (*tariffdomain.SetDefaultTariffCommand, *txfakes.Manager[tariffdomain.Repository]) {
	txManager := txfakes.NewManager[tariffdomain.Repository](tariffs)

	return tariffdomain.NewSetDefaultTariffCommand(cache, txManager), txManager
}

func TestSetDefaultTariffCommand_MovesFlagAndInvalidatesBoth(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsDefault: true},
		&tariffdomain.Tariff{ID: 2, ParkingID: 7},
	)
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	cache.Items[2] = *tariffs.Items[2]
	command, txManager := newSetDefaultCommand(tariffs, cache)

	err := command.Exec(context.Background(), 2, nil)

	require.NoError(t, err)
	assert.Equal(t, 1, txManager.Calls)
	assert.False(t, tariffs.Items[1].IsDefault)
	assert.True(t, tariffs.Items[2].IsDefault)
	assert.Empty(t, cache.Items, "кеш обоих тарифов сброшен")
}

func TestSetDefaultTariffCommand_AlreadyDefaultIsNoop(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsDefault: true})
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	command, _ := newSetDefaultCommand(tariffs, cache)

	err := command.Exec(context.Background(), 1, nil)

	require.NoError(t, err)
	assert.Contains(t, cache.Items, uint(1))
}

func TestSetDefaultTariffCommand_NotFound(t *testing.T) {
	command, _ := newSetDefaultCommand(billingfakes.NewTariffRepository(), billingfakes.NewTariffCacheRepository())

	err := command.Exec(context.Background(), 42, nil)

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindNotFound, appException.Kind)
}

func TestSetDefaultTariffCommand_OtherParkingIsNotFound(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsDefault: true},
		&tariffdomain.Tariff{ID: 2, ParkingID: 7},
	)
	command, _ := newSetDefaultCommand(tariffs, billingfakes.NewTariffCacheRepository())

	err := command.Exec(context.Background(), 2, []uint{8})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindNotFound, appException.Kind)
	assert.True(t, tariffs.Items[1].IsDefault, "флаг чужой парковки не тронут")
	assert.False(t, tariffs.Items[2].IsDefault)
}

func TestSetDefaultTariffCommand_DefaultIndexViolationIs422(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7, IsDefault: true},
		&tariffdomain.Tariff{ID: 2, ParkingID: 7},
	)
	tariffs.SetDefaultErr = fmt.Errorf("set default tariff failed (tariff_id=2): %w", tariffdomain.ErrDefaultTariffTaken)
	cache := billingfakes.NewTariffCacheRepository()
	cache.Items[1] = *tariffs.Items[1]
	cache.Items[2] = *tariffs.Items[2]
	command, txManager := newSetDefaultCommand(tariffs, cache)

	err := command.Exec(context.Background(), 2, nil)

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Equal(t, 1, txManager.Calls)
	assert.False(t, tariffs.Items[2].IsDefault)
	assert.Len(t, cache.Items, 2, "транзакция не прошла — кеш не сбрасывается")
}
