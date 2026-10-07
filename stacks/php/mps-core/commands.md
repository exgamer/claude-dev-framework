# Commands

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

> **Уточнение фреймворка:** Command/Query — это сервис, вынесенный в отдельный класс, когда операция слишком большая; работает **в пределах одного домена**. Междоменная оркестрация — не Command, а workflow (`architecture/layers.md`). Вариант 2 ниже применять только для сценариев внутри одного домена.

Command в проекте выполняет две роли — **атомарная операция** и **оркестратор сценария** (внутри домена). Живёт в `Domains/{Domain}/Modules/{Module}/Commands/`.

## Вариант 1 — атомарный Command

Одно сфокусированное действие. Переиспользуется в нескольких местах.

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
        private readonly OtherServiceInterface $otherService,
    ) {}

    public function execute(YourDTO $dto, int $rowIndex): void
    {
        $this->getLogger()->for($this);

        $this->transactionManager->run(function () use ($dto, $rowIndex) {
            $existing = $this->repository->findByExternalId($dto->externalId);

            if ($existing) {
                $this->repository->update($existing, $dto->toArray());
            } else {
                $model = $this->repository->create($dto->toArray());
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

## Вариант 2 — оркестрирующий Command

Координирует несколько сервисов и атомарных Commands для выполнения полного сценария. Вызывается из Controller, CLI `handle()`, Job.

```php
class CreateOrderCommand extends Command
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly OrderServiceInterface $orderService,
        private readonly CartServiceInterface $cartService,
        private readonly NotifyCommand $notifyCommand,
        private readonly ApplyBonusesCommand $applyBonusesCommand,
    ) {}

    private CreateOrderDTO $dto;

    public function setDto(CreateOrderDTO $dto): self
    {
        $this->dto = $dto;

        return $this;
    }

    public function prepare(): bool
    {
        $this->transactionManager->run(function () {
            $order = $this->orderService->create($this->dto);
            $this->cartService->clear($this->dto->getUserId());
            $this->notifyCommand->setOrder($order)->execute();
            $this->applyBonusesCommand->setOrder($order)->execute();
        });

        return true;
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
├─ Атомарное действие, переиспользуется в нескольких местах?
│   └─ → Атомарный Command
│
└─ Полный сценарий: несколько сервисов / доменов / побочные эффекты?
    └─ → Оркестрирующий Command
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
    // → вынести в оркестрирующий Command
}

// ❌ Жирный сервис — оркестрация внутри Service
class OrderService
{
    public function createWithNotification(...): Order
    {
        $order = $this->create(...);
        $this->bonusService->apply(...); // чужой домен
        // → вынести в оркестрирующий Command
    }
}
```
