package admin

import (
	"context"
	"fmt"
	"strconv"
	"time"

	rediscore "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/redis"
	"github.com/redis/go-redis/v9"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

const (
	keyPrefix = "parking-service:identity:admin:"
	timeout   = 2 * time.Second
)

func NewRedisRepository(client *redis.Client) *RedisRepository {
	return &RedisRepository{
		client: client,
	}
}

// RedisRepository Кеш администратора с правами; TTL задаёт сервис из конфига
type RedisRepository struct {
	client *redis.Client
}

func (r *RedisRepository) Get(ctx context.Context, adminID uint) (*admindomain.Admin, error) {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	admin, err := rediscore.NewHelper[admindomain.Admin](ctx, r.client).GetStruct(key(adminID))
	if err != nil {
		return nil, fmt.Errorf("get admin cache failed (admin_id=%d): %w", adminID, err)
	}

	return admin, nil
}

func (r *RedisRepository) Set(ctx context.Context, admin *admindomain.Admin, ttl time.Duration) error {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	if err := rediscore.NewHelper[admindomain.Admin](ctx, r.client).SetStruct(key(admin.ID), *admin, ttl); err != nil {
		return fmt.Errorf("set admin cache failed (admin_id=%d): %w", admin.ID, err)
	}

	return nil
}

func key(adminID uint) string {
	return keyPrefix + strconv.FormatUint(uint64(adminID), 10)
}
