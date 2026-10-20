package outbox_tariff_event

import "context"

// FailedAttemptTxManager Попытка и статус после неудачной отправки — атомарно
type FailedAttemptTxManager interface {
	Exec(ctx context.Context, fn func(ctx context.Context, repository Repository) error) error
}
