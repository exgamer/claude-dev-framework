package tariff

import (
	"context"
	"fmt"
	"strconv"
	"time"

	rediscore "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/redis"
	"github.com/redis/go-redis/v9"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

const (
	keyPrefix = "parking-service:tariff:"
	ttl       = 2 * time.Hour
	timeout   = 2 * time.Second
)

func NewRedisRepository(client *redis.Client) *RedisRepository {
	return &RedisRepository{
		client: client,
	}
}

type RedisRepository struct {
	client *redis.Client
}

func (r *RedisRepository) GetByID(ctx context.Context, id uint) (*tariffdomain.Tariff, error) {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	model, err := rediscore.NewHelper[tariff](ctx, r.client).GetStruct(key(id))
	if err != nil {
		return nil, fmt.Errorf("get tariff cache failed (tariff_id=%d): %w", id, err)
	}

	return tariffToEntity(model), nil
}

func (r *RedisRepository) Set(ctx context.Context, entity *tariffdomain.Tariff) error {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	if err := rediscore.NewHelper[tariff](ctx, r.client).SetStruct(key(entity.ID), *tariffFromEntity(entity), ttl); err != nil {
		return fmt.Errorf("set tariff cache failed (tariff_id=%d): %w", entity.ID, err)
	}

	return nil
}

// Invalidate Все ключи одним UNLINK
func (r *RedisRepository) Invalidate(ctx context.Context, ids ...uint) error {
	if len(ids) == 0 {
		return nil
	}

	keys := make([]string, 0, len(ids))

	for _, id := range ids {
		keys = append(keys, key(id))
	}

	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	if err := r.client.Unlink(ctx, keys...).Err(); err != nil {
		return fmt.Errorf("invalidate tariff cache failed (tariff_ids=%v): %w", ids, err)
	}

	return nil
}

func key(id uint) string {
	return keyPrefix + strconv.FormatUint(uint64(id), 10)
}
