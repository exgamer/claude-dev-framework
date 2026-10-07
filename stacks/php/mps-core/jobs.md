# Jobs (RabbitMQ consumer handlers)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

Обработчики сообщений из очереди RabbitMQ. Живут в домене, регистрируются в `config/queue.php`.

## Структура

```
Domains/{Domain}/Modules/{Module}/Jobs/
└── YourHandler.php
```

## Простой обработчик

```php
namespace App\Domains\YourDomain\Modules\YourModule\Jobs;

use App\Domains\YourDomain\Modules\YourModule\Services\YourServiceInterface;
use MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job as UtilsJob;

class YourHandler extends UtilsJob
{
    public function __construct(
        protected YourServiceInterface $service,
    ) {}

    protected function process(object|array $payload): void
    {
        $this->service->handle($payload['id']);
    }

    protected function logId(): string
    {
        return 'YOUR-DOMAIN:MODULE:ACTION'; // используется в логах, формат DOMAIN:MODULE:ACTION
    }
}
```

## С логгером и типизированным payload

```php
use MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface;
use MPS\Utils\Components\Logger\Interfaces\LoggerInterface;
use MPS\Utils\Components\Logger\Traits\LoggerAwareTrait;
use MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job as UtilsJob;

class YourHandler extends UtilsJob implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected YourServiceInterface $service,
        LoggerInterface $logger,
    ) {
        $this->setLogger($logger);
    }

    protected function process(object|array $payload): void
    {
        // payload может быть типизированным DataObject (десериализован фреймворком)
        // или сырым массивом
        match (true) {
            $payload instanceof YourDataObject => $this->service->handle($payload),
            default => throw new InvalidConfigurationAppException('Unexpected payload'),
        };
    }

    protected function logId(): string
    {
        return 'YOUR-DOMAIN:MODULE:ACTION';
    }
}
```

## Регистрация в config/queue.php

```php
// config/queue.php — в секцию consumers
[
    'queue'   => config('app.rabbit-config.your-domain.action.queue-name'),
    'handler' => \App\Domains\YourDomain\Modules\YourModule\Jobs\YourHandler::class,
],
```

## Когда использовать Job vs Command

| Ситуация | Что использовать |
|---|---|
| Событие пришло из очереди RabbitMQ | `Job` (UtilsJob) |
| Операция запускается вручную / по расписанию | `Command` |
| Нужно обработать сообщение асинхронно | `Job` + `QueueManagerInterface::putInQueue()` |
