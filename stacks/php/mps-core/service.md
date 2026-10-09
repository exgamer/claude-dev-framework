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
    public function createItem(YourDto $dto): YourModel
    {
        return $this->transactionManager->run(function () use ($dto) {
            $model = $this->repository->createFromDto($dto);
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

## Связанные данные — без `expanded`

Параметр `expanded` (и `{E}ExpandEnum`, `hasExpand()`) не используется: состав ответа эндпойнта фиксирован, клиент его не выбирает. Связанные данные, которые нужны ответу, сервис дочитывает всегда — методом **своего** репозитория пачкой по id (без N+1); данные другого домена — только workflow (O-7). Нужен другой состав — отдельный эндпойнт.

```php
$ids = array_column($items, 'id');
$periods = $this->periodRepository->allByAgreementIds($ids);   // свой домен, одна выборка
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
