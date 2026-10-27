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

Тип хелпера — модель кеша из `model.go`, не доменная сущность: неэкспортируемое состояние сущности (`isActive`, `isDefault`) в JSON не попадает (`../../structure.md`, «Infrastructure Layer»).

```go
helper := rediscore.NewHelper[product](ctx, r.client)

helper.SetStruct("key", model, 2*time.Hour)
model, err := helper.GetStruct("key")   // nil, nil если не найдено

helper.SetArray("key", models, 1*time.Hour)
models, err := helper.GetArray("key")

helper.SetString("key", "value", 30*time.Minute)
value, err := helper.GetString("key")
```

## Пример репозитория

Модуль — `model.go` (модель кеша), `mapper.go` (модель ↔ сущность, сборка через `Restore*`), `repository.go`. Каждый вызов — со своим коротким таймаутом (`context.WithTimeout`, `../../checklist.md` п. 19; кеш не должен держать запрос дольше БД), ошибка — обёрнута с ID (п. 20), ключ — только через константу префикса. Эталон — `../../examples/tariff/internal/infrastructure/redis/billing/tariff/`.

```go
// model.go
type tariff struct {
	ID        uint
	ParkingID uint
	Name      string
	IsDefault bool
	IsActive  bool
}

// mapper.go
func tariffToEntity(model *tariff) *tariffdomain.Tariff {
	if model == nil {
		return nil
	}

	return tariffdomain.RestoreTariff(tariffdomain.Tariff{
		ID:        model.ID,
		ParkingID: model.ParkingID,
		Name:      model.Name,
	}, model.IsDefault, model.IsActive)
}

func tariffFromEntity(entity *tariffdomain.Tariff) *tariff {
	if entity == nil {
		return nil
	}

	return &tariff{
		ID:        entity.ID,
		ParkingID: entity.ParkingID,
		Name:      entity.Name,
		IsDefault: entity.IsDefault(),
		IsActive:  entity.IsActive(),
	}
}

// repository.go
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
```

Таймауты Redis-репозиториев действуют только при `REDIS_CONTEXT_TIMEOUT_ENABLED=true` (`../../review-findings.md`).
