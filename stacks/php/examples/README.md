# Эталонный модуль PHP: тарифы (Laravel + mps/core)

Один модуль целиком, написанный строго по правилам фреймворка. **Образец формы**: как оформить файл — смотреть сюда; бизнес-детали — в соседнем модуле проекта. Основан на реальном `superapp-api/parking_app` (Billing/Tariffs), но без старых мест: междоменный класс — `CreateTariffWorkflow`, большая операция домена — `Commands/SetDefaultTariffCommand`, тест — в `tests/`.

- Написан под `mps/core` **1.6.0** / `mps/utils` 2.6.2 (версии superapp-api): менеджер транзакций — проектный `App\ParkingApp\Core\Database\Managers\TransactionManagerInterface`, потому что в ядре он появляется только с v1.10.0 (`mps-core/transactions.md`). В проекте с ядром ≥1.10 — интерфейс из ядра.
- `Core/*` подпроекта (`Request`, `SearchRequest`, `PaginateResource`, `PaginatedQueryHelper`, `TransactionManagerInterface`) и домен `Catalog/Parkings` считаются существующими — в примере их нет.
- **Не проверено запуском:** локально нет PHP, Docker не запущен — ни `php -l`, ни php-cs-fixer, ни phpstan, ни phpunit по примеру не прогонялись. API ядра сверено по исходникам `vendor/mps/core` 1.6.0 (`CRUDRepositoryInterface`, `DataObject`, `Service`, исключения).
- При изменении правила или версии ядра — поправить пример.

## Что где и какое правило показывает

| Файл | Что показывает |
|---|---|
| `Domains/Billing/Modules/Tariffs/DTO/TariffDto.php` | `{Entity}Dto extends DataObject`: private-поля, геттеры, fluent-сеттеры |
| `…/Enums/TariffStatusEnum.php` | backed enum, кейсы `UPPER_SNAKE` (P-16; в реальном `parking_app` тут PascalCase — старый стиль) |
| `…/Repositories/TariffRepositoryInterface.php` | интерфейс в домене, возвращает Eloquent-модель (P-4) |
| `…/Services/TariffCrudService.php` + `Interface` | сервис модуля: валидатор → `ValidationAppException`, готовые исключения ядра, без чужих доменов |
| `…/Validators/TariffDtoValidator.php` | доменные инварианты статическим валидатором (P-8) |
| `…/Commands/SetDefaultTariffCommand.php` | **Command** — логика одного домена, вынесенная из сервиса: 2 записи в `TransactionManagerInterface::run()`, метод `execute()` |
| `Domains/Billing/definitions.php`, `ServiceProvider.php` | биндинг сервиса домена |
| `Workflows/Billing/Tariffs/CreateTariffWorkflow.php` | **Workflow** — междоменная логика (Billing + Catalog), `{Action}Workflow`, `execute()` (P-2) |
| `Entrypoints/Admin/Billing/…` | контекст Admin без `src/` (P-3, P-9); тонкий контроллер с OpenAPI; `Requests/Tariffs/{Create,Update,Index}Request` (P-5); `Responses/*Response`; `routes.php` с `ApiResponseMiddleware` |
| `Infrastructure/Postgres/Billing/…` | тип хранилища в пути (P-10); модель с `@property`, схемой, `$casts`; репозиторий на `CRUDRepository` + `filterSearch`; `definitions.php`; миграция домена |
| `tests/Unit/ParkingApp/Domains/Billing/Tariffs/TariffDtoValidatorTest.php` | тест в `tests/`, путь повторяет путь класса (O-3) |
