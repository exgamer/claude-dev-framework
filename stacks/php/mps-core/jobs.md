# Очередь: обработчики RabbitMQ и отложенные Job

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Две разные вещи:

| Что | Где лежит | Класс |
|---|---|---|
| **Обработчик сообщения RabbitMQ** (consumer) — точка входа, как контроллер: сообщение пришло извне | `Entrypoints/Queue/{Domain}/{Module}/{Event}Handler.php` (контекст `Queue`, `architecture/layers.md`: consumers — Entrypoint). Эталон — `parking_app/Entrypoints/Queue/Billing/WhitelistOutbox/WhitelistChangeHandler.php` | `MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job` |
| **Отложенное действие домена** (Laravel queued job: активировать тариф в назначенное время) | `Domains/{Domain}/Modules/{Module}/Jobs/{Action}Job.php`. Эталон — `ActivatePlannedTariffJob` | `ShouldQueue` + `Queueable` |

## Обработчик RabbitMQ

Тонкий, как контроллер: payload → DTO → сервис/workflow. Бизнес-логики нет; доменные инварианты проверяет `{E}DtoValidator` в сервисе/workflow (`workflow-trusts-transport-request`). Повторная доставка безопасна (`approaches/patterns/idempotency.md`): обработчик проверяет текущий статус записи и выходит, если уже обработано.

```php
namespace App\ParkingApp\Entrypoints\Queue\YourDomain\YourModule;

use App\ParkingApp\Domains\YourDomain\Modules\YourModule\DTO\YourEventDto;
use App\ParkingApp\Workflows\YourDomain\YourModule\HandleYourEventWorkflow;
use MPS\Core\Exceptions\UnprocessableAppException;
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
            throw new UnprocessableAppException('В сообщении нет id');   // постоянная ошибка — без бесконечного повтора (outbox.md)
        }

        $this->workflow->execute($dto);
    }

    protected function logId(): string
    {
        return 'YOUR-DOMAIN:MODULE:ACTION'; // формат DOMAIN:MODULE:ACTION, уходит в логи
    }
}
```

`process()` — хук ядра; публичный `handle(QueueJob $queueJob, array $payload)` реализует `UtilsJob`. Временная ошибка (внешняя система недоступна) — исключение наверх, сообщение уйдёт в повтор; постоянная (битый payload, нет конфигурации) — зафиксировать статус и выйти без исключения, иначе повтор бесконечный.

### Регистрация в `config/queue.php`

```php
// секция consumers → handler_map
[
    'queue'   => config('app.rabbit-config.your-domain.action.queue-name'),
    'handler' => \App\ParkingApp\Entrypoints\Queue\YourDomain\YourModule\YourEventHandler::class,
],
```

Отправка — `QueueManagerInterface::putInQueue()` (RabbitMQ V2, `rules.md`); после записи и вне транзакции (`../conventions.md` п. 17).

## Отложенное действие домена (Laravel Job)

```php
namespace App\ParkingApp\Domains\YourDomain\Modules\YourModule\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ActivatePlannedTariffJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $tariffId,
    ) {
        $this->onConnection('database');
    }

    public function handle(TariffRepositoryInterface $repository, ActivatePlannedTariffCommand $command): void
    {
        $tariff = $repository->oneById($this->tariffId);
        if (! $tariff || $tariff->status !== TariffStatusEnum::SCHEDULED) {
            return;   // уже обработано или отменено — повтор безопасен
        }

        $command->execute($tariff);
    }
}
```

Ставится из сервиса/workflow после записи: `ActivatePlannedTariffJob::dispatch($tariff->id)->delay($tariff->starts_at)` (п. 17). Внутри — только вызов Command/сервиса своего домена, логики в `handle()` нет.

## Что куда

| Ситуация | Что использовать |
|---|---|
| Событие пришло из RabbitMQ (другой сервис, железо) | обработчик в `Entrypoints/Queue/…` |
| Своё действие позже / с задержкой | `Jobs/{Action}Job` домена + `dispatch()->delay()` |
| Операция по расписанию или вручную | консольная команда (`cli.md`) → Command домена |
| Событие наружу с гарантией доставки | outbox (`approaches/patterns/outbox.md`) |
