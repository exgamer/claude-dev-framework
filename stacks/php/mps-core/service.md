# Service

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Простой сервис (наследование CRUDService)

```php
namespace App\Domains\YourDomain\Modules\YourModule\Services;

use MPS\Core\Components\CRUD\Services\Database\Eloquent\CRUDService;

class YourService extends CRUDService implements YourServiceInterface
{
    public function __construct(
        YourRepositoryInterface $repository,
    ) {
        $this->repository = $repository;
    }

    public function getRepository(): YourRepositoryInterface
    {
        return $this->repository;
    }
}
```

---

## Сложный сервис (CrudServiceDecorator)

> **Версия:** `MPS\Core\Components\CRUD\Services\Database\Eloquent\CrudServiceDecorator` есть с **mps/core 1.7.0**. В проекте со старым ядром (superapp-api — 1.6.0) — `extends MPS\Core\Services\Service`, свой интерфейс, CRUD через репозиторий (как `TariffCrudService` в `../examples/`).

Когда нужны кастомные сигнатуры или сложный флоу с использованием готовых операций Crud:

```php
class YourService extends CrudServiceDecorator implements YourServiceInterface
{
    public function __construct(
        YourRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly OtherServiceInterface $otherService,
    ) {
        parent::__construct();
        $this->setRepository($repository);
    }

    // Кастомная сигнатура — не ограничена CreateCommandDataObject
    public function createItem(YourCreateDTO $dto): YourModel
    {
        return $this->transactionManager->run(function () use ($dto) {
            $model = $this->repository->create($dto->toArray());
            $this->otherService->notify($model);
            return $model;
        });
    }

    // Стандартный CRUD доступен через __call
    // $this->deleteById($id)
    // $this->search($dto)
}
```

`$this->repository->transaction(...)` здесь не используем — это жёсткая связка метода с
конкретным репозиторием ради побочного вызова `transaction()`. См. `transactions.md`.

**Интерфейс сложного сервиса НЕ наследует CRUDServiceInterface.**

---

## Expand-параметры

```php
// Enum
enum YourExpandEnum: string implements Enumerable
{
    use EnumerableTrait;

    case RELATION = 'relation';
    case ANOTHER  = 'another';
}

// В сервисе — afterSearch обогащает результаты
protected function afterSearch(SearchDataObject $dto): void
{
    $dto->processItemsCallback(function (&$items) {
        if (! $this->hasExpand(YourExpandEnum::RELATION->value)) {
            return;
        }

        $ids = array_column($items, 'id');
        $relations = $this->relationService->searchByIds($ids);

        foreach ($items as &$item) {
            $item['relation'] = $relations[$item['id']] ?? null;
        }
    });
}
```

---

## Хуки CRUDService

Допустимы только в **маленьких, простых сервисах**. В крупных сервисах не использовать — логика размазывается и становится непрозрачной. Если сервис растёт — выносить логику в `CrudServiceDecorator` или `Command`.

```php
protected function beforeCreate(CommandDataObject $dto): void {}
protected function afterCreate(CommandDataObject $dto): void {}
protected function beforeSearch(SearchDataObject $dto): void {}
protected function afterSearch(SearchDataObject $dto): void {}
```
