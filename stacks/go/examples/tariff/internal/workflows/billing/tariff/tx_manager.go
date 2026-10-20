package tariff

import (
	"context"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// CreateTariffTxManager Создание тарифа и событие tariff.created в outbox — в одной транзакции (два модифицирующих запроса)
type CreateTariffTxManager interface {
	Exec(ctx context.Context, fn func(
		ctx context.Context,
		tariffRepository tariffdomain.Repository,
		outboxRepository outboxdomain.Repository,
	) error) error
}
