# Очередь: обработчики RabbitMQ и отложенные Job

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Две разные вещи:

| Что | Где лежит | Класс |
|---|---|---|
| **Обработчик сообщения RabbitMQ** (consumer) — точка входа, как контроллер: сообщение пришло извне | `Entrypoints/Queue/{Domain}/{Module}/{Event}Handler.php` (контекст `Queue`, `architecture/layers.md`: consumers — Entrypoint). Например `Entrypoints/Queue/Billing/Tariffs/ParkingDeactivatedHandler.php` (образец — `stacks/php/examples/parking_app/Entrypoints/Queue/Billing/Tariffs/ParkingDeactivatedHandler.php`) | `MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job` |
| **Отложенное действие домена** (Laravel queued job: активировать тариф в назначенное время) | `Domains/{Domain}/Modules/{Module}/Jobs/{Action}Job.php`. Например `Domains/Billing/Modules/Tariffs/Jobs/ActivatePlannedTariffJob.php` (ниже) | `ShouldQueue` + `Queueable` |

## Обработчик RabbitMQ

Общие правила consumer — `approaches/patterns/async.md`, «Consumer». Тонкий, как контроллер: payload → DTO → сервис/workflow. Бизнес-логики нет; доменные инварианты проверяет `{E}DtoValidator` в сервисе/workflow (`workflow-trusts-transport-request`). Повторная доставка безопасна (`approaches/patterns/idempotency.md`): обработчик проверяет текущий статус записи и выходит, если уже обработано; сам переход — по `regulations/entity.md`, «Переход состояния».

```php
namespace App\YourApp\Entrypoints\Queue\YourDomain\YourModule;

use App\YourApp\Domains\YourDomain\Modules\YourModule\DTO\YourEventDto;
use App\YourApp\Workflows\YourDomain\YourModule\HandleYourEventWorkflow;
use MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job as UtilsJob;

class YourEventHandler extends UtilsJob
{
    public function __construct(
        private readonly HandleYourEventWorkflow $workflow,
    ) {
    }

    protected function process(object|array $payload): void
    {
        // Payload — внешние данные: только через DTO, не $payload['id'] (../conventions.md п. 11b)
        $dto = YourEventDto::make()->fromArray((array) $payload);
        if ($dto->getId() === null) {
            $this->errorLog('В сообщении нет id', (array) $payload);   // постоянная ошибка: повтор не поможет — лог и выход

            return;
        }

        $this->workflow->execute($dto);   // временная ошибка (OperationFailedAppException) уходит наверх — сообщение повторится
    }

    protected function logId(): string
    {
        return 'YOUR-DOMAIN:MODULE:ACTION'; // формат DOMAIN:MODULE:ACTION, уходит в логи
    }
}
```

`process()` — хук ядра; публичный `handle(QueueJob $queueJob, array $payload)` реализует `UtilsJob`. Ошибки — как в `approaches/patterns/async.md`, «Consumer»:

- **временная** (внешняя система недоступна, сеть) — `OperationFailedAppException` наверх, сообщение уйдёт в повтор;
- **постоянная** (битый payload, нет обязательного поля, нет конфигурации, запись не найдена и не появится) — зафиксировать (статус записи или `$this->errorLog(...)`) и `return`, без исключения. Своего исключения ядра для «битого payload» нет — не выдумывать.

Почему: `UtilsJob::handle` ловит любое исключение из `process()`, пишет error-лог и Sentry и отправляет сообщение в повтор с задержками из `durability.delayed` конфига очереди, после последней — в failed_jobs. Для постоянной ошибки это N бесполезных попыток и N ошибок в Sentry. Пример — эталонный `ParkingDeactivatedHandler`: нет `parking_id` — `errorLog` и `return`, сбой БД из Command — наверх, в повтор.

### Регистрация в `config/queue.php`

Обработчик — элемент `connections.rabbitmq.handler_map` (образец — `stacks/php/examples/config/queue.php`):

```php
'connections' => [
    'rabbitmq' => [
        'handler_map' => [
            [
                'queue' => config('app.rabbit-config.your-domain.your-event.queue-name'),
                'exchange' => config('app.rabbit-config.your-domain.your-event.exchange'),
                'routing_key' => config('app.rabbit-config.your-domain.your-event.routing-key'),
                'handler' => \App\YourApp\Entrypoints\Queue\YourDomain\YourModule\YourEventHandler::class,
                'durability' => ['delayed' => [60, 300, 900, 3600]],
            ],
        ],
    ],
],
```

Отправка — `QueueManagerInterface` (RabbitMQ V2, `rules.md`): `putInQueue()` — в очередь, `putInExchange()` — в exchange; после записи и вне транзакции (`../conventions.md` п. 17). Событие, которое должно уйти вместе с записью, — через outbox (`approaches/patterns/outbox.md`; в эталоне Command outbox публикует через `OutboxTariffEventPublisher` — `putInExchange`).

## Отложенное действие домена (Laravel Job)

```php
namespace App\YourApp\Domains\YourDomain\Modules\YourModule\Jobs;

use App\YourApp\Domains\YourDomain\Modules\YourModule\Commands\YourActionCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class YourActionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $id,
    ) {
        $this->onConnection('database');
    }

    public function handle(YourActionCommand $command): void
    {
        $command->execute($this->id);   // проверка статуса («уже обработано — выход») и запись — в Command, повтор безопасен
    }
}
```

Образец — `stacks/php/examples/parking_app/Domains/Billing/Modules/Tariffs/Jobs/ActivatePlannedTariffJob.php` (Command — `.../Tariffs/Commands/ActivatePlannedTariffCommand.php`). Ставится из сервиса/workflow сразу после коммита, первым из побочных эффектов: `ActivatePlannedTariffJob::dispatch($tariff->id)->delay($tariff->scheduled_at)`; страховка от потерянной постановки — крон, подбирающий просроченные (`../conventions.md` п. 17). В `handle()` — только вызов Command/сервиса своего домена: ни репозитория, ни проверок.

## Что куда

| Ситуация | Что использовать |
|---|---|
| Событие пришло из RabbitMQ (другой сервис, железо) | обработчик в `Entrypoints/Queue/…` |
| Своё действие позже / с задержкой | `Jobs/{Action}Job` домена + `dispatch()->delay()` |
| Операция по расписанию или вручную | консольная команда (`cli.md`) → Command домена |
| Событие наружу с гарантией доставки | outbox (`approaches/patterns/outbox.md`) |
