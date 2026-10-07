# RabbitMQ

## V2 — использовать (актуальный)

`MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface`

```php
// Отправка в exchange
$this->queueManager->putInExchange('exchange-name', $payload, 'routing-key');

// С заголовками
$messageConfig = MessageConfig::make()->setApplicationHeaders(['foo' => 'bar']);
$this->queueManager->putInExchange('exchange-name', $payload, 'routing-key', $messageConfig);

// Отправка в очередь
$this->queueManager->putInQueue($queueConfig, $payload);

// Batch
$this->queueManager->batchPublish($queueConfig, $payloads);
```

Полный API: `putInQueue`, `putInExchange`, `put`, `publish`, `batchPublish`,
`declareQueue`, `declareExchange`, `bindQueue`, `unbindQueue`, `purgeQueue`,
`deleteQueue`, `deleteExchange`.

### Управление топологией

```php
use MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueConfig;
use MPS\Utils\Components\Queue\RabbitMQ\DataObject\ExchangeConfig;
use MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueBindConfig;
use MPS\Utils\Components\Queue\RabbitMQ\Enums\ExchangeTypeEnum;
use PhpAmqpLib\Wire\AMQPTable;

// создание очереди
$manager->declareQueue(QueueConfig::make()->setName('some-queue'));

// с аргументами (delayed, dead-letter и т.д.)
$manager->declareQueue(
    QueueConfig::make()
        ->setName('delayed-queue')
        ->setArguments(new AMQPTable([
            'x-dead-letter-exchange'    => 'main-exchange',
            'x-dead-letter-routing-key' => 'routing-key',
            'x-message-ttl'             => 5000,
        ]))
);

// создание exchange
$manager->declareExchange(
    ExchangeConfig::make()->setName('main-exchange')->setType(ExchangeTypeEnum::DIRECT)
);

// привязка очереди к exchange
$manager->bindQueue(
    QueueBindConfig::make()
        ->setQueueName('some-queue')
        ->setExchangeName('main-exchange')
        ->setRoutingKey('routing-key')
);
```

### Конфигурация handler_map

```php
// config/queue.php — два варианта записи
'handler_map' => [
    // массив
    'some-job' => [
        'exchange_name' => 'some-exchange',
        'queue'         => 'some-queue',
        'routing_key'   => 'some-routing-key',
        'handler'       => SomeJob::class,
        'durability'    => [
            'delayed'            => [5, 10], // отложенные очереди в секундах
            'alternate_exchange' => true,
        ],
    ],

    // объект
    HandlerMapItemConfig::make()
        ->setQueue('some-queue')
        ->setExchange('some-exchange')
        ->setRoutingKey('some-routing-key')
        ->setHandler(SomeHandler::class)
        ->setDurability(
            DurabilityConfig::make()->setDelayed([5, 10])
        ),
],
```

### Инициализация очередей

```bash
php artisan mps:utils:queue:rabbit:init
```

Есть mock-объекты для тестов — указывать в `definitions.php`:

```php
[
    'abstract' => QueueManagerInterface::class,
    'concrete' => QueueManager::class,
    'mock'     => QueueManagerMock::class,
],
```

Дополнительные менеджеры: `AlternateExchangeManager`, `DelayedQueueManager`.

## V1 — deprecated, не использовать

```php
// ❌ Устарел
RabbitManager::putIn(...);
```

## Consumer Job

См. [../docs/jobs.md](../docs/jobs.md) — обработчики сообщений из очереди.
