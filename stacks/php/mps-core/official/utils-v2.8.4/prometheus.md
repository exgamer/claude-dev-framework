# Prometheus

`MPS\Utils\Components\Prometheus`

Сбор и экспорт метрик HTTP-запросов в формате Prometheus.

## Подключение

```php
// 1. ServiceProvider
\MPS\Utils\Components\Prometheus\ServiceProvider::class

// 2. config/mps-utils.php
'prometheus' => [
    'enabled' => env('PROMETHEUS_ENABLED', false),
    'default_storage' => env('PROMETHEUS_STORAGE', 'redis'),
    'storages' => [
        'redis' => [
            'host'     => env('PROMETHEUS_REDIS_HOST', '127.0.0.1'),
            'port'     => env('PROMETHEUS_REDIS_PORT', '6379'),
            'password' => env('PROMETHEUS_REDIS_PASSWORD'),
            'database' => (int) env('PROMETHEUS_REDIS_DB'),
            'adapter'  => Prometheus\Storage\RedisNg::class,
        ],
    ],
    'http_collectors' => [
        \MPS\Utils\Components\Prometheus\Collectors\RequestTotalCollector::class,
        \MPS\Utils\Components\Prometheus\Collectors\RequestDurationCollector::class,
    ],
],

// 3. Middleware (bootstrap/app.php)
$middleware->append(\MPS\Utils\Components\Prometheus\Http\Middleware\MetricMiddleware::class);
```

Endpoint экспорта: `GET /api/metric/export` — авторегистрируется при `enabled: true`.

## Типы метрик

| Тип | Применение | Особенность |
|---|---|---|
| Counter | HTTP-запросы, ошибки, события | Только растёт, анализируется через `rate()` |
| Gauge | Использование памяти, размер очереди | Может расти и падать, состояние «сейчас» |
| Histogram | Latency запросов, размеры ответов | Бакеты + sum + count, можно считать перцентили |
| Summary | Локальные перцентили внутри процесса | Не агрегируется между инстансами |

## Готовые коллекторы

- `RequestTotalCollector` — количество HTTP-запросов (counter)
- `RequestDurationCollector` — длительность запросов (histogram)

## Кастомный коллектор

```php
use MPS\Utils\Components\Prometheus\Interfaces\HttpCollectorInterface;

class CustomCollector implements HttpCollectorInterface
{
    public function collect(RegistryInterface $registry, Request $request, Response $response): void
    {
        // своя логика
    }
}
```

## Кастомные метрики (не HTTP)

```php
use MPS\Utils\Components\Prometheus\DataObjects\CounterMetric;
use MPS\Utils\Components\Prometheus\Interfaces\MetricManagerInterface;

$counter = app(MetricManagerInterface::class)->getRegistry()
    ->getOrRegisterCounter(
        CounterMetric::make()
            ->setNamespace('cron')
            ->setHelp('Daily report executions')
            ->setName(MetricName::CLI)
            ->setLabels(['command'])
    );

$counter->inc(['app:report:daily']);
```

## Очистка

```bash
php artisan mps:utils:prometheus:wipe-storage
```
