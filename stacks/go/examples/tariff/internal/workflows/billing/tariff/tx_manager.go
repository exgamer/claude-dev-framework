package tariff

import (
	"context"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

// CreateTariffTxManager Проверка парковки и создание тарифа — в одной транзакции
type CreateTariffTxManager interface {
	Exec(ctx context.Context, fn func(ctx context.Context, parkingRepository parkingdomain.Repository, tariffRepository tariffdomain.Repository) error) error
}
