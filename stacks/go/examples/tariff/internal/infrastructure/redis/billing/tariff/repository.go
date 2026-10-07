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

const ttl = 2 * time.Hour

func NewRedisRepository(client *redis.Client) *RedisRepository {
	return &RedisRepository{
		client: client,
	}
}

type RedisRepository struct {
	client *redis.Client
}

func (r *RedisRepository) GetByID(ctx context.Context, id uint) (*tariffdomain.Tariff, error) {
	return rediscore.NewHelper[tariffdomain.Tariff](ctx, r.client).GetStruct(key(id))
}

func (r *RedisRepository) Set(ctx context.Context, tariff *tariffdomain.Tariff) error {
	return rediscore.NewHelper[tariffdomain.Tariff](ctx, r.client).SetStruct(key(tariff.ID), *tariff, ttl)
}

func (r *RedisRepository) Invalidate(ctx context.Context, id uint) error {
	if err := r.client.Unlink(ctx, key(id)).Err(); err != nil {
		return fmt.Errorf("invalidate tariff cache failed (tariff_id=%d): %w", id, err)
	}

	return nil
}

func key(id uint) string {
	return "parking-service:tariff:" + strconv.FormatUint(uint64(id), 10)
}
