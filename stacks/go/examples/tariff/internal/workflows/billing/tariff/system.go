package tariff

import (
	"context"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// TariffNotifier Сообщает партнёрской биллинговой системе о новом тарифе
type TariffNotifier interface {
	NotifyCreated(ctx context.Context, tariff *tariffdomain.Tariff) error
}
