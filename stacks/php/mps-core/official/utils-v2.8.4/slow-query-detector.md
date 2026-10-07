# SlowQueryDetector

`MPS\Utils\Components\Database\SlowQueryDetector`

Слушает все SQL-запросы, логирует медленные. Фильтрует стек-трейс — оставляет только
не-vendor файлы.

## Использование

Invokable класс, активируется в `boot()` ServiceProvider:

```php
public function boot(): void
{
    (new SlowQueryDetector())();
}
```

## Конфигурация

```php
// config/mps-utils.php
'database' => [
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // ms
],
```
