# Redis

Пакет: `github.com/exgamer/gosdk-redis-core/pkg/redis`

## Схема ключей

```
{service-name}:{entity}:{id}
{service-name}:{entity}:{field1}:{field2}   // составной ключ если нужно

# Примеры:
parking-session-service:tariff:123
parking-session-service:tariff-periods:123
parking-session-service:session:42:A123BC    // parkingId:licensePlate
```

## Helper методы

```go
helper := rediscore.NewHelper[domain.Product](ctx, r.client)

helper.SetStruct("key", product, 2*time.Hour)
product, err := helper.GetStruct("key")   // nil, nil если не найдено

helper.SetArray("key", products, 1*time.Hour)
products, err := helper.GetArray("key")

helper.SetString("key", "value", 30*time.Minute)
value, err := helper.GetString("key")
```

## Пример репозитория

```go
const keyPrefix = "parking-session-service:tariff:"

func NewRedisRepository(client *redis.Client) *RedisRepository {
    return &RedisRepository{client: client}
}

type RedisRepository struct{ client *redis.Client }

func (r *RedisRepository) Set(ctx context.Context, m *domain.Tariff) error {
    return rediscore.NewHelper[domain.Tariff](ctx, r.client).
        SetStruct(keyPrefix+strconv.FormatUint(uint64(m.ID), 10), *m, 2*time.Hour)
}

func (r *RedisRepository) GetById(ctx context.Context, id uint) (*domain.Tariff, error) {
    return rediscore.NewHelper[domain.Tariff](ctx, r.client).
        GetStruct(keyPrefix + strconv.FormatUint(uint64(id), 10))
}

// Инвалидация нескольких ключей сразу
func (r *RedisRepository) Invalidate(ctx context.Context, id uint) error {
    idStr := strconv.FormatUint(uint64(id), 10)
    return r.client.Unlink(
        ctx,
        "parking-session-service:tariff:"+idStr,
        "parking-session-service:tariff-periods:"+idStr,
    ).Err()
}
```
