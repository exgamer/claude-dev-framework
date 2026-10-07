# Authenticate

`MPS\Utils\Components\Authenticate\Basic\BasicAuthMiddleware`

Basic Auth middleware для защиты эндпоинтов.

## Конфигурация

```php
// config/basic-auth.php
return [
    'swagger-ui' => [
        'username' => env('SWAGGER_UI_USERNAME'),
        'password' => env('SWAGGER_UI_PASSWORD'),
    ],
];
```

## Enum для групп (рекомендуется)

```php
use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

enum BasicAuthenticateGroupEnum: string implements Enumerable
{
    use EnumerableTrait;

    case SWAGGER_UI = 'swagger-ui';
}
```

## Подключение к маршруту

```php
Route::middleware(['basic.auth:' . BasicAuthenticateGroupEnum::SWAGGER_UI->value])
    ->group(function () {
        Route::get('/internal/endpoint', ...);
    });
```

Второй аргумент — путь к конфигу (по умолчанию `basic-auth`):

```php
Route::middleware(['basic.auth:group_name,custom-config'])->...
```

## Подключение к l5-swagger

```php
// config/l5-swagger.php
'middleware' => [
    'api' => [
        BasicAuthMiddleware::class . ':' . BasicAuthenticateGroupEnum::SWAGGER_UI->value,
    ],
],
```

## Ответы

| Код | Причина |
|---|---|
| `401 Unauthorized` | Неверные или отсутствующие учётные данные |
| `500 Internal Server Error` | Группа не найдена в конфиге |
