package outbox_tariff_event

import (
	"context"
	"errors"
	"fmt"
	"time"

	"gorm.io/gorm"
	"gorm.io/gorm/clause"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

func NewPostgresRepository(client *gorm.DB) *PostgresRepository {
	return &PostgresRepository{
		client: client,
	}
}

type PostgresRepository struct {
	client *gorm.DB
}

func (r *PostgresRepository) Create(ctx context.Context, entity *outboxdomain.OutboxTariffEvent) (*outboxdomain.OutboxTariffEvent, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	model := entityToModel(entity)

	if err := r.client.WithContext(ctx).Create(model).Error; err != nil {
		return nil, fmt.Errorf("create outbox tariff event failed (tariff_id=%d): %w", entity.TariffID, err)
	}

	return modelToEntity(model), nil
}

func (r *PostgresRepository) FindByID(ctx context.Context, id uint) (*outboxdomain.OutboxTariffEvent, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var model outboxTariffEvent
	result := r.client.WithContext(ctx).
		Where("id = ?", id).
		First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, fmt.Errorf("find outbox tariff event failed (id=%d): %w", id, result.Error)
	}

	return modelToEntity(&model), nil
}

func (r *PostgresRepository) MarkPublished(ctx context.Context, id uint) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	err := r.client.WithContext(ctx).
		Model(&outboxTariffEvent{}).
		Where("id = ?", id).
		Where("status IN ?", []uint{uint(outboxdomain.StatusPending), uint(outboxdomain.StatusAwaitingRetry)}).
		Updates(map[string]any{
			"status":       uint(outboxdomain.StatusPublished),
			"published_at": gorm.Expr("now()"),
		}).
		Error
	if err != nil {
		return fmt.Errorf("mark outbox tariff event published failed (id=%d): %w", id, err)
	}

	return nil
}

func (r *PostgresRepository) MarkFailed(ctx context.Context, id uint) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	err := r.client.WithContext(ctx).
		Model(&outboxTariffEvent{}).
		Where("id = ?", id).
		Where("status IN ?", []uint{uint(outboxdomain.StatusPending), uint(outboxdomain.StatusAwaitingRetry)}).
		Update("status", uint(outboxdomain.StatusFailed)).
		Error
	if err != nil {
		return fmt.Errorf("mark outbox tariff event as failed (id=%d): %w", id, err)
	}

	return nil
}

func (r *PostgresRepository) MarkAwaitingRetry(ctx context.Context, id uint) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	err := r.client.WithContext(ctx).
		Model(&outboxTariffEvent{}).
		Where("id = ?", id).
		Where("status IN ?", []uint{uint(outboxdomain.StatusPending), uint(outboxdomain.StatusAwaitingRetry)}).
		Update("status", uint(outboxdomain.StatusAwaitingRetry)).
		Error
	if err != nil {
		return fmt.Errorf("mark outbox tariff event awaiting retry failed (id=%d): %w", id, err)
	}

	return nil
}

func (r *PostgresRepository) IncrementAttempts(ctx context.Context, id uint) (uint, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var attempts uint

	err := r.client.WithContext(ctx).
		Raw(`UPDATE parking.outbox_tariff_events
			SET attempts = attempts + 1, updated_at = now()
			WHERE id = ?
			RETURNING attempts`, id).
		Scan(&attempts).
		Error
	if err != nil {
		return 0, fmt.Errorf("increment outbox tariff event attempts failed (id=%d): %w", id, err)
	}

	return attempts, nil
}

// ClaimForRetry Один UPDATE: подзапрос FOR UPDATE SKIP LOCKED выбирает зависшие строки, UPDATE сдвигает updated_at — так их не возьмёт
// параллельный прогон, RETURNING отдаёт ID. Блокировка живёт только в этом запросе, публикация идёт без транзакции
func (r *PostgresRepository) ClaimForRetry(ctx context.Context, limit uint, staleAfter time.Duration) ([]uint, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	stale := r.client.
		Model(&outboxTariffEvent{}).
		Select("id").
		Where("status IN ?", []uint{uint(outboxdomain.StatusPending), uint(outboxdomain.StatusAwaitingRetry)}).
		Where("updated_at < now() - make_interval(secs => ?)", staleAfter.Seconds()).
		Order("id").
		Limit(int(limit)).
		Clauses(clause.Locking{Strength: "UPDATE", Options: "SKIP LOCKED"})

	var models []outboxTariffEvent
	result := r.client.WithContext(ctx).
		Model(&models).
		Clauses(clause.Returning{Columns: []clause.Column{{Name: "id"}}}).
		Where("id IN (?)", stale).
		Update("updated_at", gorm.Expr("now()"))

	if result.Error != nil {
		return nil, fmt.Errorf("claim outbox tariff events for retry failed: %w", result.Error)
	}

	ids := make([]uint, 0, len(models))

	for _, model := range models {
		ids = append(ids, model.ID)
	}

	return ids, nil
}
