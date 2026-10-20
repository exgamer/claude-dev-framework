package tariff

import (
	"context"
	"errors"
	"fmt"
	"testing"

	"github.com/jackc/pgx/v5/pgconn"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// driverError Ошибка, как её отдаёт GORM поверх pgx: *pgconn.PgError в цепочке обёрток
func driverError(code, constraint string) error {
	return fmt.Errorf("exec: %w", &pgconn.PgError{Code: code, ConstraintName: constraint})
}

func TestSetDefaultError_DefaultIndexViolationIsDomainError(t *testing.T) {
	err := setDefaultError(driverError("23505", defaultTariffUniqueIndex), 2)

	require.ErrorIs(t, err, tariffdomain.ErrDefaultTariffTaken)
	assert.Contains(t, err.Error(), "tariff_id=2")
}

func TestSetDefaultError_OtherFailuresStayInternal(t *testing.T) {
	cases := map[string]error{
		"другой уникальный индекс": driverError("23505", "tariffs_other_unique"),
		"другой SQLSTATE":          driverError("23503", defaultTariffUniqueIndex),
		"не ошибка Postgres":       errors.New("connection reset"),
	}

	for name, driverErr := range cases {
		t.Run(name, func(t *testing.T) {
			err := setDefaultError(driverErr, 2)

			require.Error(t, err)
			assert.NotErrorIs(t, err, tariffdomain.ErrDefaultTariffTaken)
			assert.ErrorIs(t, err, driverErr)
		})
	}
}

func TestSetDefaultError_NilIsNil(t *testing.T) {
	assert.NoError(t, setDefaultError(nil, 2))
}

func TestPostgresRepository_GetByID_ZeroScopeFindsNothingWithoutQuery(t *testing.T) {
	repository := NewPostgresRepository(nil) // клиента нет: запрос к БД упал бы

	tariff, err := repository.GetByID(context.Background(), 1, tariffdomain.ParkingScope{})

	require.NoError(t, err)
	assert.Nil(t, tariff)
}
