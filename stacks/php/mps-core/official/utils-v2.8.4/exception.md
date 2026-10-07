# Exception

`MPS\Utils\Components\Exception\Factories\ExceptionContextFactory`

Нормализует любой `Throwable` в DTO для логирования или передачи в очередь/внешний сервис.

## Использование

```php
use MPS\Utils\Components\Exception\Factories\ExceptionContextFactory;

try {
    // ...
} catch (Throwable $e) {
    $context = ExceptionContextFactory::make($e, withTrace: true);

    $this->logger->error('Operation failed', $context->toArray());

    // или передать в очередь
    $this->queueManager->putInQueue($config, $context->toArray());
}
```

## ExceptionContext — поля

| Поле | Тип | Описание |
|---|---|---|
| `exception` | string | FQCN класса исключения |
| `message` | string | Сообщение |
| `code` | int\|string | Код |
| `file` | string | Файл |
| `line` | int | Строка |
| `statusCode` | ?int | HTTP-статус (только для AppException) |
| `appErrorCode` | ?AppErrorCodeEnumInterface | Код ошибки приложения |
| `details` | array | Детали (только для AppException) |
| `trace` | array | Стек-трейс (только при `withTrace: true`) |
| `previous` | ?array | Предыдущее исключение (`exception`, `message`) |
