# Commands

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

> **Уточнение фреймворка:** Command/Query — это сервис, вынесенный в отдельный класс, когда операция слишком большая; работает **в пределах одного домена**. Агрегатная и междоменная логика (сценарий над несколькими доменами, координация их сервисов и побочных эффектов) — только workflow (`architecture/layers.md`, O-7), не Command.

Command — **большая операция одного домена**. Живёт в `Domains/{Domain}/Modules/{Module}/Commands/`.

## Command одного домена

Одна операция одного домена, слишком большая для метода сервиса.

### execute() с явным аргументом

```php
namespace App\Domains\YourDomain\Modules\YourModule\Commands;

use MPS\Core\Commands\Command;
use MPS\Core\Components\Database\Transaction\TransactionManagerInterface;
use MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface;
use MPS\Utils\Components\Logger\Traits\LoggerAwareTrait;

class YourSaveCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly YourRepositoryInterface $repository,
        private readonly OtherServiceInterface $otherService, // модуль своего домена (O-6)
    ) {}

    public function execute(YourDto $dto, int $rowIndex): void
    {
        $this->getLogger()->for($this);

        $this->transactionManager->run(function () use ($dto, $rowIndex) {
            $existing = $this->repository->findByExternalId($dto->externalId);

            if ($existing) {
                $this->repository->updateFromDto($existing, $dto);
            } else {
                $model = $this->repository->createFromDto($dto);
                $this->otherService->notify($model);
            }
        });
    }
}
```

### prepare() с fluent-сеттерами

Когда параметры задаются поэтапно или команда переиспользуется с разными наборами данных:

```php
class YourCommand extends Command
{
    private array $data = [];
    private ?User $user = null;

    public function __construct(
        private readonly YourServiceInterface $service,
    ) {}

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function prepare(): bool
    {
        $this->service->doSomething($this->data, $this->user);

        return true;
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
