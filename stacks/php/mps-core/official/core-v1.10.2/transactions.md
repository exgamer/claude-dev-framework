# Transactions

`MPS\Core\Components\Database\Transaction\TransactionManagerInterface` / `MPS\Core\Components\Database\Transaction\TransactionManager`

Управление транзакциями БД на уровне сервиса — когда в одной транзакции участвуют несколько репозиториев
и `Repository::transaction()` конкретного репозитория не подходит.

Биндинг `TransactionManagerInterface → TransactionManager` (singleton) регистрируется в `MPS\Core\Components\Database\ServiceProvider`
через `singletonIf`, поэтому в приложении его можно переопределить через `definitions.php`.

Для тестов без БД есть `NoopTransactionManager` — callback выполняется без транзакции, `afterCommit` — сразу:

```php
[
    'abstract' => TransactionManagerInterface::class,
    'concrete' => TransactionManager::class,
    'mock' => NoopTransactionManager::class,
],
```

## Использование

```php
use MPS\Core\Components\Database\Transaction\TransactionManagerInterface;

class OrderService
{
    public function __construct(
        private readonly TransactionManagerInterface $transactionManager,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function create(CreateOrderDto $dto): Order
    {
        return $this->transactionManager->run(function () use ($dto) {
            $order = $this->orderRepository->create($dto->toArray());
            $this->paymentRepository->create(['order_id' => $order->id]);

            // событие уйдёт только после фиксации корневой транзакции
            $this->transactionManager->afterCommit(fn () => OrderCreated::dispatch($order));

            return $order;
        });
    }
}
```

## Методы

| Метод | Описание |
|---|---|
| `run(callable $callback, int $attempts = 1)` | Выполняет callback в транзакции, возвращает его результат. `attempts` — число попыток при deadlock; при `> 1` callback выполняется повторно, побочные эффекты внутри него тоже повторятся |
| `begin()` / `commit()` / `rollBack()` | Ручное управление транзакцией (вложенные — через savepoint) |
| `level()` | Текущий уровень вложенности |
| `inTransaction()` | `level() > 0` |
| `afterCommit(callable $callback)` | Выполнить после коммита корневой транзакции; при откате не вызывается; вне транзакции — сразу |
| `onConnection(?string $connection)` | Копия менеджера для другого соединения, исходный экземпляр не меняется |

```php
$this->transactionManager->onConnection('pgsql_reports')->run(fn () => ...);
```
