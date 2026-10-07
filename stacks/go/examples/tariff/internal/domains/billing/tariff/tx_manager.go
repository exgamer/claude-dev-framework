package tariff

import "context"

// DefaultTariffTxManager Смена тарифа по умолчанию: снять флаг со старого и поставить новому — атомарно
type DefaultTariffTxManager interface {
	Exec(ctx context.Context, fn func(ctx context.Context, repository Repository) error) error
}
