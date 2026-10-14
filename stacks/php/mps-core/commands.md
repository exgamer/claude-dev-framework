# Commands

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

> **Уточнение фреймворка:** Command/Query — это сервис, вынесенный в отдельный класс, когда операция слишком большая; работает **в пределах одного домена**. Агрегатная и междоменная логика (сценарий над несколькими доменами, координация их сервисов и побочных эффектов) — только workflow (`architecture/layers.md`, O-7), не Command.

Command — **большая операция одного домена**. Живёт в `Domains/{Domain}/Modules/{Module}/Commands/`.

## Command одного домена

Одна операция одного домена, слишком большая для метода сервиса.

### Форма

Точка входа — `execute()` с типизированными аргументами (DTO, id), результат — модель/значение (`../style.md`, «Имена»). `Command::prepare()` ядра и fluent-сеттеры с массивами (`setData(array)`) в новом коде не используются: параметры команды — аргументы `execute()`, данные — DTO (п. 11a).

```php
namespace App\ParkingApp\Domains\YourDomain\Modules\YourModule\Commands;

use MPS\Core\Commands\Command;
use MPS\Core\Components\Database\Transaction\TransactionManagerInterface;
use MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface;
use MPS\Utils\Components\Logger\Traits\LoggerAwareTrait;

final class SaveYourEntityCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly YourRepositoryInterface $repository,
        private readonly OtherServiceInterface $otherService, // модуль своего домена (O-6)
    ) {
    }

    public function execute(YourDto $dto): YourModel
    {
        $this->getLogger()->for($this);

        $model = $this->transactionManager->run(function () use ($dto) {
            $existing = $this->repository->getByExternalId($dto->getExternalId());
            if ($existing) {
                $this->repository->updateFromDto($existing, $dto);

                return $existing;
            }

            return $this->repository->createFromDto($dto);
        });

        $this->otherService->notify($model);   // побочный эффект — после коммита (п. 17)

        return $model;
    }
}
```

## Сценарий над несколькими доменами — Workflow, не Command

«Оркестрирующего Command» нет: координация сервисов разных доменов (заказ + корзина + бонусы + уведомление) — `Workflows/{D}/{M}/{Action}Workflow::execute()`, транзакционен по умолчанию (O-8). Workflow берёт репозитории и сервисы любых доменов напрямую (O-7), Command/Query/Service домена чужой домен не берут — **[ОШИБКА]**, тег `domain-cross-domain-access`. Эталон — `../examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php`.

```php
final class CreateOrderWorkflow
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly OrderServiceInterface $orderService,     // домен Orders
        private readonly CartServiceInterface $cartService,       // домен Carts
        private readonly ApplyBonusesCommand $applyBonusesCommand, // домен Bonuses
    ) {
    }

    public function execute(CreateOrderDto $dto): Order
    {
        return $this->transactionManager->run(function () use ($dto) {
            $order = $this->orderService->create($dto);
            $this->cartService->clear($dto->getUserId());
            $this->applyBonusesCommand->execute($order->id);

            return $order;
        });
    }
}
```

Оркестратор зависит только от `TransactionManagerInterface`, а не от репозитория одного из
координируемых сервисов (`$this->orderService->getRepository()->transaction()`) — см. `transactions.md`.

---

## Когда Command, когда Service

```
Нужна операция?
│
├─ CRUD одной сущности, нет побочных эффектов в других доменах?
│   └─ → Service
│
├─ Большая операция одного домена (много шагов, зависимостей, ветвлений)?
│   └─ → Command (изменение) / Query (чтение) в модуле
│
└─ Сценарий над 2+ доменами / агрегатная логика?
    └─ → Workflow
```

---

## Антипаттерны

```php
// ❌ Жирный контроллер — логика в store()
public function store(Request $request): JsonResponse
{
    $order = $this->orderService->create(...);
    $this->cartService->clear(...);
    $this->notifyCommand->execute();
    // → вынести в workflow
}

// ❌ Жирный сервис — оркестрация внутри Service
class OrderService
{
    public function createWithNotification(...): Order
    {
        $order = $this->create(...);
        $this->bonusService->apply(...); // чужой домен
        // → вынести в workflow
    }
}
```
