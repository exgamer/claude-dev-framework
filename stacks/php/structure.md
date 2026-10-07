# Структура PHP-сервиса (Laravel + mps/core + mps/utils)

Эталоны — подпроекты монолита `superapp-api` (`~/MPIProjects/mps/aina/superapp-api`):

| Эталон | Кто писал | Как использовать |
|---|---|---|
| `parking_app/` (`App\ParkingApp\`) | автор фреймворка | **основной** — форма модулей, нейминг, стиль |
| `super_app/` (`App\SuperApp\`) | команда | второй — где совпадает с `parking_app`, это подтверждённая практика; где расходится — спор (P-* в `core/decisions.md`) |
| `app/` | легаси | **не эталон**. Новый код туда не пишется (см. `approaches/process/legacy-refactor.md`) |

Слои те же, что в Go (`architecture/layers.md`); здесь — как они выглядят в Laravel. Соответствие Go ↔ PHP — в конце файла.

---

## Дерево подпроекта

```
parking_app/                                   ← корень подпроекта = PSR-4 App\ParkingApp\
├── ServiceProvider.php                        ← регистрирует провайдеры доменов, инфраструктуры, точек входа
├── Core/                                      ← общее для подпроекта, не бизнес-логика
│   ├── Database/Managers/                     ← TransactionManagerInterface, NoopTransactionManager
│   ├── Database/Query/PaginatedQueryHelper.php
│   ├── Requests/{Request,SearchRequest}.php   ← базовые FormRequest
│   ├── Resources/                             ← PaginateResource, схемы ошибок для OpenAPI
│   ├── Http/Middleware/
│   ├── Rules/                                 ← кастомные правила валидации (PhoneRule, LicensePlateRule)
│   └── Enums/, Helpers/, OpenApi/
│
├── Domains/{Domain}/
│   ├── ServiceProvider.php                    ← extends Core\Providers\ServiceProvider (подхватывает definitions.php)
│   ├── definitions.php                        ← биндинги сервисов домена: Interface → класс
│   └── Modules/{Module}/
│       ├── DTO/{Entity}Dto.php                ← extends MPS\Core\DataObjects\DataObject
│       ├── Enums/{Entity}StatusEnum.php
│       ├── Repositories/{Entity}RepositoryInterface.php   ← только интерфейс
│       ├── Services/{Entity}CrudService.php + {Entity}CrudServiceInterface.php
│       ├── Services/{Context}{Entity}Service.php  ← только если логика для контекста отличается (P-13)
│       ├── Commands/{Action}Command.php       ← большая операция изменения внутри домена, метод execute()
│       ├── Queries/{Action}Query.php          ← большая операция чтения внутри домена, метод execute()
│       ├── Validators/{Entity}DtoValidator.php            ← доменная валидация DTO
│       ├── Jobs/                              ← отложенные задачи домена
│       └── Components/{Name}/                 ← изолированный алгоритм (калькулятор и т.п.) со своими DTO/Enums/Utils/Tests
│
├── Workflows/{Domain}/{Module}/               ← только междоменная агрегатная логика
│   └── {Action}Workflow.php                   ← метод execute() (решение P-2)
│
├── Entrypoints/{Context}/{Domain}/
│   ├── ServiceProvider.php                    ← extends MPS\Core\Modules\ModuleServiceProvider (подхватывает routes.php)
│   ├── routes.php                             ← все маршруты контекста+домена
│   └── {Module}/Http/
│       ├── Controllers/{Entity}Controller.php
│       ├── Requests/{Entity}/{Create,Update,Index}Request.php
│       └── Responses/{Entity}Response.php, {Entity}PaginatedResponse.php
│
└── Infrastructure/
    ├── ServiceProvider.php
    ├── Postgres/{Domain}/
    │   ├── Providers/ServiceProvider.php, definitions.php     ← RepositoryInterface → Repository
    │   ├── Providers/database/migrations/                     ← миграции домена
    │   └── {Module}/Models/{Entity}.php, Repositories/{Entity}Repository.php
    ├── Postgres/Common/Providers/definitions.php              ← TransactionManagerInterface → DatabaseTransactionManager (mock: Noop)
    ├── Redis/, Cache/{Domain}/{Module}/                       ← кэш-репозитории
    ├── Http/{Provider}/{Product}/                             ← адаптер внешней системы, по её имени (решение G-2)
    ├── Http/{Service}/{Module}/                               ← наш другой микросервис
    └── RabbitMQ/
```

**Infrastructure — всегда с типом** (решение P-10): `Infrastructure/{Postgres,Redis,Http,RabbitMQ,…}/…`. `Infrastructure/{Domain}/` без типа (как `super_app/Infrastructure/Acms`, `Infrastructure/User/Models`) в новом коде — **[ВНИМАНИЕ]**.

**`Core/` — только техническое общее подпроекта** (решение P-11): транзакции, пагинация, базовые Request/Resource, middleware, правила валидации. Общей бизнес-логики «для всех доменов» нет: нужна двум доменам — workflow или свой домен. `Domains/Shared/` (как в `super_app`) в новом коде не заводится.

**Слой `Contexts/` из `Backend_Architecture_Reglament.md` проекта = `Entrypoints/`** (решение P-9): отдельной папки `Contexts/` нет, контекст — первый уровень внутри `Entrypoints/` (`Entrypoints/Admin/…`). Пустую `parking_app/Contexts/` образцом не считать.

**Контексты (`{Context}`)** — для кого точка входа: `Admin`, `Mobile`, `Integration`, `Lpm` (железо/партнёр), `Console`, `Queue`. Один и тот же модуль для разных контекстов — разные директории и разные контроллеры (см. `Backend_Architecture_Reglament.md` проекта: «контексты — разные продукты, а не роли», `if ($isAdmin)` запрещён).

**Без `src/`** (решение P-3): `Domains/{D}/Modules/{M}/`, `Entrypoints/{Ctx}/{D}/{M}/`, один PSR-4 на подпроект. Вариант `super_app` (`Domains/{D}/src/…` + PSR-4 на каждый домен) в новом коде не используется; существующие модули `super_app` не переписываются без задачи.

---

## Пути

| Что | Путь (`parking_app`) | Namespace |
|---|---|---|
| DTO | `Domains/{D}/Modules/{M}/DTO/{E}Dto.php` | `App\ParkingApp\Domains\{D}\Modules\{M}\DTO` |
| Интерфейс репозитория | `Domains/{D}/Modules/{M}/Repositories/{E}RepositoryInterface.php` | `…\Modules\{M}\Repositories` |
| Сервис | `Domains/{D}/Modules/{M}/Services/{E}CrudService.php` (+ `Interface`) | `…\Modules\{M}\Services` |
| Валидатор DTO | `Domains/{D}/Modules/{M}/Validators/{E}DtoValidator.php` | `…\Modules\{M}\Validators` |
| Command / Query | `Domains/{D}/Modules/{M}/Commands/{Action}Command.php` / `Queries/{Action}Query.php` | `…\Modules\{M}\Commands` / `…\Queries` |
| Workflow | `Workflows/{D}/{M}/{Action}Workflow.php`, метод `execute()` | `App\ParkingApp\Workflows\{D}\{M}` |
| Контроллер | `Entrypoints/{Ctx}/{D}/{M}/Http/Controllers/{E}Controller.php` | `App\ParkingApp\Entrypoints\{Ctx}\{D}\{M}\Http\Controllers` |
| Request | `Entrypoints/{Ctx}/{D}/{M}/Http/Requests/{E}/CreateRequest.php` | `…\Http\Requests\{E}` |
| Response | `Entrypoints/{Ctx}/{D}/{M}/Http/Responses/{E}Response.php` | `…\Http\Responses` |
| Маршруты | `Entrypoints/{Ctx}/{D}/routes.php` | — |
| Модель | `Infrastructure/Postgres/{D}/{M}/Models/{E}.php` | `App\ParkingApp\Infrastructure\Postgres\{D}\{M}\Models` |
| Репозиторий | `Infrastructure/Postgres/{D}/{M}/Repositories/{E}Repository.php` | `…\{M}\Repositories` |
| Биндинг репозитория | `Infrastructure/Postgres/{D}/Providers/definitions.php` | — |
| Миграция | `Infrastructure/Postgres/{D}/Providers/database/migrations/` | — |

---

## Чеклист нового модуля

**Обязательно:**
- [ ] `Domains/{D}/Modules/{M}/DTO/{E}Dto.php`
- [ ] `Domains/{D}/Modules/{M}/Repositories/{E}RepositoryInterface.php`
- [ ] `Domains/{D}/Modules/{M}/Services/{E}CrudService.php` + `{E}CrudServiceInterface.php`
- [ ] биндинг сервиса в `Domains/{D}/definitions.php`
- [ ] `Infrastructure/Postgres/{D}/{M}/Models/{E}.php`
- [ ] `Infrastructure/Postgres/{D}/{M}/Repositories/{E}Repository.php`
- [ ] биндинг репозитория в `Infrastructure/Postgres/{D}/Providers/definitions.php`
- [ ] миграция в `Infrastructure/Postgres/{D}/Providers/database/migrations/`
- [ ] `Entrypoints/{Ctx}/{D}/{M}/Http/Controllers/{E}Controller.php` с OpenAPI-атрибутами
- [ ] `Entrypoints/{Ctx}/{D}/{M}/Http/Requests/{E}/{Create,Update,Index}Request.php`
- [ ] `Entrypoints/{Ctx}/{D}/{M}/Http/Responses/{E}Response.php` (+ `{E}PaginatedResponse.php` для списка)
- [ ] маршруты в `Entrypoints/{Ctx}/{D}/routes.php`
- [ ] новый домен/контекст — провайдер добавлен в корневой `ServiceProvider.php` подпроекта

**По необходимости:**
- [ ] `Validators/{E}DtoValidator.php` — если есть доменные инварианты сверх формы запроса
- [ ] `Enums/` — статусы и типы
- [ ] `Commands/{Action}Command.php` / `Queries/{Action}Query.php` в модуле — если операция слишком большая для сервиса
- [ ] `Workflows/{D}/{M}/{Action}Workflow.php` — если сценарий трогает другой домен
- [ ] `Jobs/` — отложенные действия домена

---

## Go SDK ↔ Laravel + mps/core

| Понятие | Go (`gosdk-*`) | PHP (`mps/core`) |
|---|---|---|
| Сборка приложения | `app.go` + `bootstrap/{module}/*_factory.go` | `ServiceProvider.php` + `definitions.php` |
| DI | `di.Register` / `di.Resolve` только в bootstrap | контейнер Laravel через `definitions.php` (`abstract`/`concrete`/`mock`) |
| Доменная модель | `entity.go` (чистая структура) | Eloquent-модель из Infrastructure — разрешено (решение P-4) |
| DTO | `dto.go` | `DTO/{E}Dto.php extends DataObject` |
| Репозиторий | интерфейс в домене, `PostgresRepository` в infra | `{E}RepositoryInterface extends CRUDRepositoryInterface`, `{E}Repository extends CRUDRepository` |
| Маппер | `mapper.go` на каждом слое | нет: `DataObject::toArrayWithSnakeKeys()` / `fromArray()`, ответ — `JsonResource` |
| Большая операция внутри домена | `domains/{d}/{m}/{action}_command.go` / `_query.go`, метод `Exec` | `Domains/{D}/Modules/{M}/Commands/{Action}Command.php` / `Queries/…Query.php`, метод `execute` |
| Междоменная логика | `workflows/{d}/{m}/{action}_workflow.go`, метод `Exec` | `Workflows/{D}/{M}/{Action}Workflow.php`, метод `execute()` |
| Ошибки | `exception.New*Exception` → `response.ErrorResponse` | `MPS\Core\Exceptions\*AppException` → `ApiResponseMiddleware` |
| Транзакция | `dbtransaction.Manager*` | `TransactionManagerInterface::run()` |
| Маршруты | `routes.go` + регистрация в `module.go` | `routes.php` рядом с `ServiceProvider` контекста |
| Swagger | swag-аннотации | `OpenApi\Attributes` (`#[OA\…]`) на контроллере, Request, Response |
| Миграции | gormigrate через `cmd/console` | Laravel-миграции в `Infrastructure/Postgres/{D}/Providers/database/migrations/` |

---

## Тесты (решение O-3)

Только в дереве `tests/` (`autoload-dev`, `Tests\`), в продовую автозагрузку не попадают.

| Что | Где |
|---|---|
| unit: сервис, валидатор, Command/Query, компонент | `tests/Unit/{Подпроект}/…`, путь повторяет путь класса: `tests/Unit/ParkingApp/Domains/Billing/Tariffs/TariffDtoValidatorTest.php` |
| feature: HTTP через приложение, БД | `tests/Feature/{Подпроект}/{Context}/{Domain}/…`, как `tests/Feature/ParkingApp/Lpm/…` |
| моки зависимостей | `mock` в `definitions.php` (как `NoopTransactionManager`) |

Тест внутри подпроекта (`parking_app/…/Tests/`) в новом коде — **[ВНИМАНИЕ]**. Известный случай: `TariffCalculatorTest` лежит рядом с кодом, а набор «Tariff Component» в `phpunit.xml` указывает на несуществующий `parking_app/Domains/Billing/src/`, поэтому тест не запускается.
