package tariff

import (
	"context"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

// CreateTariffTxManager Проверка парковки, создание тарифа и событие tariff.created в outbox — в одной транзакции
type CreateTariffTxManager interface {
	Exec(ctx context.Context, fn func(
		ctx context.Context,
		parkingRepository parkingdomain.Repository,
		tariffRepository tariffdomain.Repository,
		outboxRepository outboxdomain.Repository,
	) error) error
}
