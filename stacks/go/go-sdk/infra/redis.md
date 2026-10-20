# Redis

Пакет: `git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/redis` (как в `go.mod` эталона; GitHub — `github.com/exgamer/gosdk-redis-core/pkg/redis`)

## Схема ключей

Правило — `approaches/patterns/cache.md` (префикс сервиса, все параметры значения в ключе). В Go префикс — константа в файле репозитория:

```
{service-name}:{entity}:{id}
{service-name}:{entity}:{field1}:{field2}   // составной ключ если нужно

# Примеры:
{service}:tariff:123
{service}:tariff-periods:123
{service}:session:42:A123BC    // parkingId:licensePlate
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

Каждый вызов — со своим коротким таймаутом (`context.WithTimeout`, `../../checklist.md` п. 19; кеш не должен держать запрос дольше БД), ошибка — обёрнута с ID (п. 20). Эталон — `../../examples/tariff/internal/infrastructure/redis/billing/tariff/repository.go`.

```go
const (
	keyPrefix = "{service}:tariff:"
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

func (r *RedisRepository) GetByID(ctx context.Context, id uint) (*domain.Tariff, error) {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	tariff, err := rediscore.NewHelper[domain.Tariff](ctx, r.client).GetStruct(key(id))
	if err != nil {
		return nil, fmt.Errorf("get tariff cache failed (tariff_id=%d): %w", id, err)
	}

	return tariff, nil
}

func (r *RedisRepository) Set(ctx context.Context, tariff *domain.Tariff) error {
	ctx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	if err := rediscore.NewHelper[domain.Tariff](ctx, r.client).SetStruct(key(tariff.ID), *tariff, ttl); err != nil {
		return fmt.Errorf("set tariff cache failed (tariff_id=%d): %w", tariff.ID, err)
	}

	return nil
}

// Invalidate Один UNLINK на все ключи всех записей, а не вызов на запись
func (r *RedisRepository) Invalidate(ctx context.Context, ids ...uint) error {
	if len(ids) == 0 {
		return nil
	}

	keys := make([]string, 0, len(ids)*2)

	for _, id := range ids {
		idStr := strconv.FormatUint(uint64(id), 10)
		keys = append(keys, keyPrefix+idStr, "{service}:tariff-periods:"+idStr)
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
```

Таймауты Redis-репозиториев действуют только при `REDIS_CONTEXT_TIMEOUT_ENABLED=true` (`../../review-findings.md`).
