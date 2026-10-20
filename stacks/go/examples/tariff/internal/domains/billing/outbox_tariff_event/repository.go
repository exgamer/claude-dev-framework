package outbox_tariff_event

import (
	"context"
	"time"
)

type Repository interface {
	Create(ctx context.Context, entity *OutboxTariffEvent) (*OutboxTariffEvent, error)
	FindByID(ctx context.Context, id uint) (*OutboxTariffEvent, error)
	// MarkPublished Только из StatusPending/StatusAwaitingRetry: повтор не перезаписывает итог
	MarkPublished(ctx context.Context, id uint) error
	// MarkFailed Только из StatusPending/StatusAwaitingRetry: опоздавшая неудача не затирает StatusPublished
	MarkFailed(ctx context.Context, id uint) error
	// MarkAwaitingRetry Только из StatusPending/StatusAwaitingRetry, как MarkFailed
	MarkAwaitingRetry(ctx context.Context, id uint) error
	// IncrementAttempts Возвращает число попыток после увеличения
	IncrementAttempts(ctx context.Context, id uint) (uint, error)
	// ClaimForRetry ID до limit зависших событий (StatusPending/StatusAwaitingRetry, не менялись дольше staleAfter);
	// взятые скрыты от параллельного прогона на staleAfter, строки, взятые другим прогоном, пропускаются
	ClaimForRetry(ctx context.Context, limit uint, staleAfter time.Duration) ([]uint, error)
}
