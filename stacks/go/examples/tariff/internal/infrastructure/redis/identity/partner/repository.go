package partner

import (
	"context"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
)

const (
	nonceKeyPrefix = "parking-service:identity:partner:nonce:"
	timeout        = 2 * time.Second
)

func NewRedisRepository(client *redis.Client) *RedisRepository {
	return &RedisRepository{
		client: client,
	}
}

// RedisRepository Использованные nonce подписей партнёра; SET NX — атомарно, два одинаковых запроса не пройдут оба
type RedisRepository struct {
	client *redis.Client
}

func (r *RedisRepository) Remember(ctx context.Context, nonce string, ttl time.Duration) (bool, error) {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	fresh, err := r.client.SetNX(ctx, nonceKeyPrefix+nonce, 1, ttl).Result()
	if err != nil {
		return false, fmt.Errorf("remember signature nonce failed: %w", err)
	}

	return fresh, nil
}
