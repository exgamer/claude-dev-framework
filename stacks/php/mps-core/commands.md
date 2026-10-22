# Commands

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

> **Уточнение фреймворка:** Command/Query — это сервис, вынесенный в отдельный класс, когда операция слишком большая; работает **в пределах одного домена**. Агрегатная и междоменная логика (сценарий над несколькими доменами, координация их сервисов и побочных эффектов) — только workflow (`architecture/layers.md`, O-7), не Command.

Command — **большая операция одного домена** (изменение), Query — то же для чтения (`queries.md`).

## Форма Command и Query

Основное место правила формы для Command и Query (`queries.md` ссылается сюда). Решение пользователя 2026-10-20: «наследника Query реализовать prepare() — эти вещи для queries и command никогда не используем, делаем просто функцию handle с необходимым количеством аргументов», затем уточнение: метод — `execute(...)`, как у workflow. Почему: базовый класс ядра передаёт параметры через состояние объекта (`setQueryParams()`/сеттеры → `get()` → `prepare()`), сигнатура операции не видна и не типизируется.

- **Класс** — простой `final` класс **без базового класса ядра**: `MPS\Core\Commands\Command`, `MPS\Core\Queries\Query` и подобные не наследуются; их `prepare()`, `get()`, `setQueryParams()`, fluent-сеттеры с массивами (`setData(array)`) не используются (описание в `official/core-v1.10.2/{commands,queries}.md` — факт API ядра, не образец).
- **Точка входа** — публичный `execute(...)` с нужным количеством типизированных аргументов (DTO, id, скаляры; данные — DTO, п. 11a), результат — модель/DTO/значение или `void` (`../style.md`, «Имена»).
- **Зависимости** — через конструктор (`private readonly`), интерфейс самого класса — только по `../conventions.md` п. 5.
- **Логирование** — только если операции есть что логировать; `LoggerAwareInterface` + `LoggerAwareTrait` (`capabilities.md`) тогда подключаются, обязательными для Command/Query они не являются.
- Workflow — тоже `execute()` (P-2), но это другой слой: 2+ доменов (`../structure.md`). Консольная команда Artisan (`*ConsoleCommand extends Illuminate\Console\Command`) и Job — Laravel-метод `handle()` со своей сигнатурой (`cli.md`, `jobs.md`); Command домена они получают параметром `handle()` и вызывают его `execute()`.

Command/Query с базовым классом ядра, `prepare()`/`get()` или точкой входа не `execute(...)` с типизированными аргументами — тег `command-query-form` (уровень — `stacks/php/review-tags.md`).

## Command одного домена

Одна операция одного домена, слишком большая для метода сервиса. Живёт в `Domains/{Domain}/Modules/{Module}/Commands/`.

```php
namespace App\YourApp\Domains\YourDomain\Modules\YourModule\Commands;

// core < 1.10 (эталон, 1.6.0) — интерфейс подпроекта; core ≥ 1.10 — MPS\Core\Components\Database\Transaction\TransactionManagerInterface (transactions.md)
use App\YourApp\Core\Database\Managers\TransactionManagerInterface;
use MPS\Core\Exceptions\NotFoundAppException;

final class ReplaceYourEntityCommand
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly YourRepositoryInterface $repository,
        private readonly OtherServiceInterface $otherService, // модуль своего домена (O-6)
    ) {
    }

    /**
     * @throws NotFoundAppException
     */
    public function execute(int $id, YourDto $dto): YourModel
    {
        $current = $this->repository->oneById($id);   // проверка — до run(), гонку закрывает concurrency.md
        if (! $current) {
            throw new NotFoundAppException('Запись не найдена');
        }

        // два модифицирующих запроса — одна транзакция (O-8); записи — на месте, не в отдельном методе
        $model = $this->transactionManager->run(function () use ($current, $dto): YourModel {
            $this->repository->archive($current->id);

            return $this->repository->createFromDto($dto);
        });

        $this->otherService->notify($model);   // побочный эффект — после коммита (п. 17)

        return $model;
    }
}
```

Один модифицирующий запрос («найти → один `update` или один `create`») — без `run()` (тег `needless-transaction`, `approaches/patterns/transactions.md`): гонку «найти или создать» закрывает уникальный индекс (`approaches/patterns/concurrency.md`), не транзакция.

Эталоны — `../examples/parking_app/Domains/Billing/Modules/Tariffs/Commands/SetDefaultTariffCommand.php`, `ActivatePlannedTariffCommand.php`, `.../OutboxTariffEvents/Commands/{Publish,Retry}OutboxTariffEvent*Command.php`.

## Сценарий над несколькими доменами — Workflow, не Command

«Оркестрирующего Command» нет: координация сервисов разных доменов (заказ + корзина + бонусы + уведомление) — `Workflows/{D}/{M}/{Action}Workflow::execute()`; транзакция — если больше одного модифицирующего запроса (O-8, `approaches/patterns/transactions.md`). Workflow берёт репозитории и сервисы любых доменов напрямую (O-7), Command/Query/Service домена чужой домен не берут — тег `domain-cross-domain-access`. Эталон — `../examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php`.

`TransactionManagerInterface` в примере ниже — по версии ядра, как в Command выше.

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
