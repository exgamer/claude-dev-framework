# Service

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Простой сервис (наследование CRUDService)

Только для чтения справочника (поиск/показ ядра). Запись — не `create()`/`update()` ядра с массивом, а свои методы репозитория с DTO (`createFromDto`/`updateFromDto`, `../conventions.md` п. 11a, P-18, тег `write-array-instead-of-dto`); хуки `beforeCreate`/`afterUpdate` ядра для записи автора не используются.

Зависимости — `private readonly` (`../conventions.md` п. 6). Исключение — сам `$repository`: это `protected ?CRUDRepositoryInterface` из `CRUDRepositoryAwareTrait` ядра, переобъявить его `private readonly` нельзя (PHP: фатальная ошибка), поэтому он присваивается в конструкторе.

```php
namespace App\ParkingApp\Domains\YourDomain\Modules\YourModule\Services;

use MPS\Core\Components\CRUD\Services\Database\Eloquent\CRUDService;

class YourService extends CRUDService implements YourServiceInterface
{
    public function __construct(
        YourRepositoryInterface $repository,
        private readonly OtherServiceInterface $otherService,
    ) {
        $this->repository = $repository;   // свойство ядра, не private readonly — см. выше
    }

    public function getRepository(): YourRepositoryInterface
    {
        return $this->repository;
    }
}
```

---

## Сложный сервис (CrudServiceDecorator)

> **Версия:** `MPS\Core\Components\CRUD\Services\Database\Eloquent\CrudServiceDecorator` есть с **mps/core 1.7.0**. В проекте со старым ядром (например 1.6.0) — `extends MPS\Core\Services\Service`, свой интерфейс, CRUD через репозиторий (как `TariffCrudService` в `../examples/`).

Когда нужны кастомные сигнатуры или сложный флоу с использованием готовых операций Crud:

```php
class YourService extends CrudServiceDecorator implements YourServiceInterface
{
    public function __construct(
        YourRepositoryInterface $repository,
        private readonly OtherServiceInterface $otherService,
    ) {
        parent::__construct();
        $this->setRepository($repository);
    }

    // Кастомная сигнатура — не ограничена CreateCommandDataObject
    public function createItem(YourDto $dto): YourModel
    {
        $model = $this->repository->createFromDto($dto);   // один модифицирующий запрос — без своей транзакции (O-8, needless-transaction)
        $this->otherService->notify($model);   // побочный эффект — после коммита (../conventions.md п. 17)

        return $model;
    }

    // Стандартный CRUD доступен через __call
    // $this->deleteById($id)
}
```

Больше одного модифицирующего запроса — внедрённый `TransactionManagerInterface::run()`; `$this->repository->transaction(...)` не используем — это жёсткая связка метода с
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

Хуки записи (`beforeCreate`/`afterCreate`/`beforeUpdate`/`afterUpdate`) не используются: `CRUDService` — только для чтения, запись — свои методы репозитория с DTO (раздел выше, P-18). Логика вокруг записи — в методе сервиса или `Command`.

`search()`, `beforeSearch`/`afterSearch(SearchDataObject)` — форма (1) списка (P-19): `repository.md`, «Форма (1)»; в форме (2) список — метод репозитория `paginate…` (`../conventions.md` п. 2c). Какая форма — раздел «Список» `design.md`.
