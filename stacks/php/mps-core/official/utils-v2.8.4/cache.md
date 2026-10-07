# Cache

## CacheManager

`MPS\Utils\Components\Cache\Interfaces\CacheManagerInterface`

Базовый кеш без тегов. Инжектируется напрямую или через `CacheManagerAwareInterface`.

```php
use MPS\Utils\Components\Cache\Interfaces\CacheManagerInterface;

class YourService
{
    public function __construct(
        private readonly CacheManagerInterface $cache,
    ) {}

    public function getData(int $id): mixed
    {
        $key = "entity:{$id}";

        return $this->cache->getOrSet($key, function () use ($id) {
            return $this->repository->findById($id);
        }, ttl: 3600);
    }
}
```

### Методы

```php
$cache->get(string $key): mixed
$cache->getMultiple(array $keys): array                       // [key => value|null, ...]
$cache->set(string $key, mixed $value, ?int $ttl = null): bool
$cache->setMultiple(array $values, ?int $ttl = null): bool    // ['key' => value, ...]
$cache->forget(string|array $key): bool                       // одиночный или batch
$cache->getOrSet(string $key, \Closure $action, ?int $ttl = null): mixed
$cache->setTtl(int $seconds): static                          // меняет TTL по умолчанию
$cache->viaNull(\Closure $callback): mixed                    // выполняет без кеша (NullStore)
```

TTL по умолчанию: 86400 (24 часа).

### Формирование ключей

Для формирования ключей использовать `KeyHelper::generate()` — объединяет части через `:`, приводит к нижнему регистру:

```php
use MPS\Utils\Components\Redis\Helpers\KeyHelper;

$key = KeyHelper::generate('order', (string) $id);          // 'order:42'
$key = KeyHelper::generate('user', 'orders', (string) $id); // 'user:orders:42'
```

**Соглашение об именовании ключей**

Части ключа разделяются через `:` по схеме `{домен}:{сущность}:{идентификатор}`:

```
order:item:42
catalog:product:offer:99
user:profile:7
```

Почему `:` а не `.` или `/`:
- Redis-клиенты (RedisInsight и др.) используют `:` как разделитель для отображения ключей в виде дерева — можно визуально навигировать по пространству имён
- `SCAN` и `KEYS` поддерживают glob-паттерны: `order:item:*` найдёт все ключи домена
- Конвенция принята в экосистеме Redis де-факто, знакома любому разработчику

Что не стоит делать:

```php
// ❌ плоский ключ без структуры — невозможно найти группу ключей паттерном
KeyHelper::generate('orderitem42');

// ❌ разные разделители — ломает навигацию в клиентах и паттерны
KeyHelper::generate('order.item', '42');

// ❌ camelCase — KeyHelper приводит к lowercase, результат непредсказуем
KeyHelper::generate('orderItem', '42'); // 'orderitem:42'
```

### Deprecated

```php
// getCacheKeyByParts — deprecated since v2.6.4
// Использовать: MPS\Utils\Components\Redis\Helpers\KeyHelper::generate(...$parts)
$cache->getCacheKeyByParts('entity', (string) $id);

// getOrCreate — deprecated since v2.6.4
// Использовать: getOrSet()
$cache->getOrCreate($key, fn () => ..., $ttl);
```

---

## SmartCache

`MPS\Utils\Components\Cache\Interfaces\SmartCacheInterface`

Фасад поверх `CacheManager` + `TaggedCacheManager` + `CallbackManager`.
Используется когда нужны теги, инвалидация по тегу или авто-обновление (remember).

### Базовое использование

```php
use MPS\Utils\Components\Cache\DataObjects\CacheOptions;
use MPS\Utils\Components\Cache\DataObjects\CallbackOptions;
use MPS\Utils\Components\Cache\Interfaces\SmartCacheInterface;

class YourService
{
    public function __construct(
        private readonly SmartCacheInterface $smartCache,
    ) {}
}
```

### CacheOptions

Иммутабельный объект с fluent-builder API:

```php
// Только TTL
$options = CacheOptions::make()->ttl(3600);

// С тегами
$options = CacheOptions::make()->ttl(3600)->tags('orders', 'users');

// С авто-обновлением при инвалидации тега
$options = CacheOptions::make()->ttl(3600)->tags('orders')->remember();
```

### CallbackOptions

Описывает вызов для авто-обновления кеша. Принимает класс, метод и параметры — не Closure:

```php
// class + method + params (резолвится через контейнер)
$callback = new CallbackOptions(
    class: YourRepository::class,
    method: 'findById',
    params: [$id],
);

// Или через фабричный метод
$callback = CallbackOptions::make(YourRepository::class, 'findById', [$id]);
```

### Методы SmartCache

```php
// Чтение
$smartCache->get(string $key): mixed
$smartCache->getMultiple(array $keys): array
$smartCache->getByTag(string $tag): array                // [key => value, ...]

// Запись
$smartCache->set(string $key, mixed $value, CacheOptions $options): bool
$smartCache->setMultiple(array $values, CacheOptions $options): bool

// Удаление
$smartCache->forget(string|array $key): bool

// Инвалидация по тегу
$smartCache->invalidate(string $tag): bool
$smartCache->invalidateMultiple(array $tags): bool

// Чтение с вычислением и кешированием
$smartCache->getOrSet(string $key, CallbackOptions $callbackOptions, CacheOptions $cacheOptions): mixed
```

### Примеры

```php
$options = CacheOptions::make()->ttl(3600)->tags('orders');

// Запись с тегом
$smartCache->set('order:42', $data, $options);

// Batch-запись
$smartCache->setMultiple(['order:1' => $d1, 'order:2' => $d2], $options);

// Чтение значений тега
$smartCache->getByTag('orders'); // ['order:42' => [...], ...]

// Инвалидация всего тега (удаляет ключи из кеша и очищает тег)
$smartCache->invalidate('orders');

// getOrSet — вычисляет и кеширует
$callback = CallbackOptions::make(YourRepository::class, 'findById', [$id]);
$options  = CacheOptions::make()->ttl(3600)->tags('orders');

$value = $smartCache->getOrSet('order:42', $callback, $options);
```

### remember — авто-обновление при инвалидации

Когда `remember()` выставлен, `invalidate()` перед удалением ключей вызывает сохранённый `CallbackOptions` и переписывает кеш свежими данными.

```php
$callback = CallbackOptions::make(YourRepository::class, 'findById', [$id]);
$options  = CacheOptions::make()->ttl(3600)->tags('orders')->remember();

// При инвалидации тега 'orders' кеш автоматически пересчитается через CallbackOptions
$smartCache->getOrSet('order:42', $callback, $options);

$smartCache->invalidate('orders'); // → вызовет YourRepository::findById($id), обновит кеш
```

---

## TaggedCacheManager

`MPS\Utils\Components\Cache\Interfaces\TaggedCacheManagerInterface`

Низкоуровневый менеджер тегов. Используется внутри `SmartCache`; напрямую — только для диагностики или сложных сценариев.

```php
// Зарегистрировать ключ под тегом
$tagged->set(string $key, string $tag): bool
$tagged->setMultiple(array $keys, string $tag): bool

// Получить ключи тега (без загрузки значений)
$tagged->getKeysByTag(string $tag): array

// Получить [key => value] по тегу (пропускает отсутствующие ключи)
$tagged->getByTag(string $tag): array

// Инвалидировать тег (удаляет ключи из кеша и очищает тег)
$tagged->invalidate(string $tag): bool
$tagged->invalidateMultiple(array $tags): bool
```

---

## Диагностика

```bash
php artisan mps:utils:cache:test
```

Прогоняет сценарии для `CacheManager`, `TaggedCacheManager` и `SmartCache` (включая `remember`) и выводит результат каждого assert.
