# Транзакции

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## По умолчанию — уже обёрнуто

`CRUDService` автоматически оборачивает `create()`, `update()`, `delete()` в транзакцию
через `$this->getRepository()->transaction()`. Дополнительно оборачивать не нужно.

---

## Когда добавлять транзакцию вручную

**Workflow — всегда, если пишет** (решение O-8, `approaches/patterns/transactions.md`): проверки
и все записи `execute()` — внутри одного `run()`, даже если запись одна. Сервисы доменов внутри
`run()` попадают в ту же транзакцию (транзакция на соединении, вложенные — savepoint).

Service/Command домена — когда одна операция затрагивает **несколько репозиториев или сервисов**
и частичное выполнение недопустимо.

Типичные места:
- Command домена с несколькими записями (`SetDefaultTariffCommand`: снять флаг со старого тарифа и поставить новому)
- Метод Service, который вызывает несколько `repository`-методов подряд

---

## Откуда брать интерфейс — mps/core

> **Версия:** `MPS\Core\Components\Database\Transaction\*` есть с **mps/core 1.10.0** (сверено по v1.10.2, официальная дока — `official/core-v1.10.2/transactions.md`). В проектах с core < 1.10 (superapp-api — 1.6.0) — проектный `Core/Database/Managers/TransactionManagerInterface` (см. `../examples/`); при обновлении ядра — переключить биндинг на ядро, сигнатура `run()` совместима.

Начиная с `mps/core` **v1.10.0** менеджер транзакций живёт в ядре, отдельная реализация в
проекте не нужна:

```php
use MPS\Core\Components\Database\Transaction\TransactionManagerInterface;
use MPS\Core\Components\Database\Transaction\TransactionManager;
use MPS\Core\Components\Database\Transaction\NoopTransactionManager;
```

- Биндинг `TransactionManagerInterface → TransactionManager` регистрируется в
  `MPS\Core\Components\Database\ServiceProvider` через `singletonIf` — приложение может
  переопределить его своим `definitions.php`.
- Методы: `run(callable $callback, int $attempts = 1)`, `afterCommit(callable $callback)`,
  `begin` / `commit` / `rollBack`, `level()`, `inTransaction()`,
  `onConnection(?string $connection)` — возвращает копию менеджера для другого соединения,
  исходный не меняется.
- `NoopTransactionManager` — заглушка для тестов без БД: callback выполняется без транзакции,
  `afterCommit` — сразу. Подключается как `mock` в `definitions.php`.

Старый проектный `App\Core\Database\Managers\TransactionManagerInterface` совместим по сигнатуре
`run()`. В проектах, где он ещё используется, новый код пишем на интерфейс из ядра, старый
мигрируем по мере правок — две реализации одновременно держать не нужно.

---

## Как использовать — TransactionManagerInterface

`$this->repository->transaction(...)` / `$this->someService->transaction(...)` — **антипаттерн**
на уровне Service/Command. Это жёсткая связка: оркестратор тянет транзакционность через чужой
репозиторий (`$this->orderService->getRepository()->transaction(...)`), хотя ему нужен только факт
транзакции, а не сам репозиторий. Из-за этого метод сложнее тестировать (нужен реальный/мокнутый
репозиторий ради одного вызова `transaction()`) и он ломается, если у координируемого сервиса
поменяется репозиторий или он вовсе перестанет иметь собственный репозиторий.

Правильно — внедрить `TransactionManagerInterface` через конструктор и вызвать `run()`:

```php
use MPS\Core\Components\Database\Transaction\TransactionManagerInterface;

final readonly class CreateAgreementCommand
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AgreementServiceInterface $agreementService,
        private AgreementPeriodServiceInterface $agreementPeriodService,
    ) {
    }

    public function execute(AgreementDto $dto): Agreement
    {
        return $this->transactionManager->run(function () use ($dto) {
            $agreement = $this->agreementService->create($dto);
            $this->agreementPeriodService->createForAgreement($agreement->id, $dto->getPeriods());

            return $agreement;
        });
    }
}
```

```php
// ✅ Внутри Service — тот же принцип
class AgreementTransactionService extends Service implements AgreementTransactionServiceInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        protected AgreementTransactionRepositoryInterface $repository,
        protected AgreementPeriodServiceInterface $periodService,
    ) {
    }

    public function create(AgreementTransactionDto $dto): AgreementTransaction
    {
        return $this->transactionManager->run(fn () => $this->createTransaction($dto));
    }
}
```

```php
// ❌ Жёсткая связка через чужой репозиторий
$this->orderService->getRepository()->transaction(function () use ($dto) {
    $this->orderService->update($updateDto);
    $this->paymentService->create($paymentDto);
});
```

`DB::transaction()` напрямую — только если нужны специфичные PostgreSQL-инструкции
внутри той же транзакции:

```php
// Допустимо только при необходимости SQL-уровня
DB::transaction(function () use ($closure) {
    DB::statement("SET LOCAL app.product_indexing_enabled = 'DISABLED'");
    return $closure();
});
```

---

## Где НЕ нужна транзакция

- Одиночный `create` / `update` / `delete` через `CRUDService` — уже обёрнут (кроме вызова из workflow: там проверки и запись — в одной транзакции workflow)
- Операции только на чтение (`search`, `find`, `get`)
- Очередь сообщений: транзакция не защищает от дублей в RabbitMQ

---

## Вложенные транзакции

PostgreSQL поддерживает savepoints — вложенные вызовы `transaction()` корректны.
`CRUDService` часто вызывается изнутри другой транзакции — это нормально.
