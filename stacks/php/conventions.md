# Правила написания кода (PHP)

Обязательные правила при написании и ревью. Как пользоваться конкретными классами ядра — `mps-core/*.md`; замечания из реальных MR — `review-findings.md`.

---

## Слои

1. **Контроллер тонкий** — Request → `{E}Dto::make()->fromArray($request->validated())` → сервис/workflow → `Response`. Никаких запросов к БД, ветвлений по бизнес-правилам, сборки массивов.
2. **Бизнес-логика — в сервисе или workflow.** Модель и репозиторий бизнес-логики не содержат: репозиторий = SELECT/INSERT/UPDATE/DELETE и фильтры поиска.
3. **Service, Command/Query, Workflow — разные вещи** (`architecture/layers.md`). Workflow берёт репозитории и сервисы любых доменов напрямую; Command/Query/Service — только своего домена (O-7). Операция слишком большая для сервиса → `Commands/{Action}Command` (изменение) / `Queries/{Action}Query` (чтение) в том же модуле домена. Нужен другой домен → workflow в `Workflows/`. Пример workflow: создание тарифа проверяет парковку (Catalog) и вызывает `TariffCrudService` (Billing) — Billing-сервис о Catalog не знает. В `parking_app` такие классы пока называются `*Command` и лежат в `Workflows/` — это старый нейминг, не образец.
4. **Репозиторий возвращает модель.** `{E}RepositoryInterface` может возвращать Eloquent-модель `Infrastructure/Postgres/{D}/{M}/Models/{E}` (решение P-4) — сервисы и workflow работают с ней. Модель не уходит в ответ напрямую: только через `{E}Response`.
5. **Интерфейсы:** `RepositoryInterface` — всегда; `ServiceInterface` — всегда; Command/Query — без интерфейса, пока его не подменяют в тестах и не инжектируют через границу домена.
6. **Зависимости — через конструктор**, `private readonly`, тип — интерфейс. `app()`/`resolve()` внутри бизнес-кода запрещены.
7. **DI — только через `definitions.php`** (домен — `Domains/{D}/definitions.php`, репозитории — `Infrastructure/Postgres/{D}/Providers/definitions.php`). `$this->app->bind()` в провайдерах — только для подпроектных портов с переопределением извне (как `ParkingAccessResolverInterface`).
8. **Контексты не пересекаются.** Логика для Admin и Mobile отличается → отдельный сервис/репозиторий в контексте со своим интерфейсом; `if ($isAdmin)` и «универсальный» сервис с флагами запрещены (регламент проекта, п. 5).

## Логика, отличающаяся по контексту

- Если для разных контекстов (Admin, Mobile…) логика различается, это отдельные классы в **том же модуле домена**, с контекстом в имени (решение P-13): `Services/{Context}{Entity}Service.php` + `{Context}{Entity}ServiceInterface.php`. Если отличается выборка, так же называется репозиторий: `{Context}{Entity}RepositoryInterface` и реализация в Infrastructure.
- Общий сервис модуля не меняется. Контекстный может его использовать через интерфейс, но не наследует и не переопределяет (регламент проекта, п. 5).
- Сервисы и репозитории в `Entrypoints/` — **[ОШИБКА]**: точка входа только вызывает нужный контекстный сервис.
- Подпапки контекстов внутри модуля (`Admin/Services/…`) не заводятся. Если у модуля набирается 3+ класса для одного контекста, это повод для нового решения: строка в `rules-log.md` задачи.

## Модели и внешние системы

- **Eloquent-связей в моделях нет** (`BelongsTo`, `HasMany`…): связанные данные собираются через репозитории (тег `model-has-eloquent-relations`). Так во всех моделях `parking_app` и `super_app`.
- **Клиент внешней системы — `{System}{Purpose}Gateway`** в `Infrastructure/Http/…` (`LpmIntercomGateway`, `LpmCameraGateway`), не `*Service` (тег `gateway-named-as-service`). В Go тот же слой называется `Repository` (`stacks/go/conventions.md`, п. 14) — различие стеков, не ошибка.
- Конфиг Gateway — через конструктор; отсутствует обязательный — `InvalidConfigurationAppException`, не молчаливый дефолт.

## Данные и валидация

9. **Форма запроса — в FormRequest** (`rules()`): типы, обязательность, `gt:0` для id, `max:` для строк/массивов, `Rule::enum()`. Без запросов к БД.
10. **Доменные инварианты — в `{E}DtoValidator::validate($dto): array`** (решение P-8; inline-проверки в сервисе/workflow, как в части `super_app`, — **[ВНИМАНИЕ]**) (статический, `final`), сервис бросает `ValidationAppException('VALIDATION ERROR', $errors)`. Формат ошибок — `['field' => ['сообщение']]`.
11. **Между слоями — DTO, не массив.** `DataObject` с private-свойствами, геттерами и fluent-сеттерами (`return $this`). В репозиторий уходит `$dto->toArrayWithSnakeKeys()`.
12. **PUT — полные валидные данные** (O-10, **[ОШИБКА]**). В `UpdateRequest` каждое поле `required`, а очищаемое — `present|nullable`; сервис пишет `$dto->toArrayWithSnakeKeys()` целиком. `DataObject` отдаёт `null` и за неприсланные свойства, поэтому необязательное поле в `UpdateRequest` (`nullable`/`sometimes` без `present`) при записи DTO целиком затирает данные `null`. Обходы — список пришедших полей рядом с DTO, отслеживание пришедших полей в DTO, `array_filter(!is_null)` — не применять.

    ```php
    // плохо: запрос {name} обнулит currency и grace_minutes
    'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],

    // хорошо
    'currency' => ['present', 'nullable', Rule::enum(CurrencyEnum::class)],
    ```

## Ошибки

13. **Только исключения ядра:** `NotFoundAppException`, `ValidationAppException`, `BadRequestAppException`, `AccessDeniedAppException`, `OperationFailedAppException`. `\Exception`, `\RuntimeException` — **[ОШИБКА]**; `new AppException(..., AppErrorTypeEnum::X)` при наличии готового класса — **[ВНИМАНИЕ]** (решение P-12).
14. Сообщения для клиента — по-русски (`'Тариф не найден'`).
15. `@throws` у каждого метода, который бросает.

## Транзакции и побочные эффекты

16. **Workflow транзакционен по умолчанию** (O-8): проверки и все записи `execute()` — внутри одного `TransactionManagerInterface::run()`; без транзакции — только с причиной в «Границах транзакций» `design.md` (`approaches/patterns/transactions.md`). Service/Command домена: несколько записей, частичное выполнение недопустимо → `run()`. Биндинг: `DatabaseTransactionManager`, mock: `NoopTransactionManager`. Не `DB::transaction`, не транзакция в репозитории.
17. **Побочные эффекты** (очередь, `Job::dispatch`, HTTP, файлы) — после фиксации записи и вне транзакции. Отложенное действие — `Job::dispatch(...)->delay(...)`, как `ActivatePlannedTariffJob`.

## HTTP и документация

18. Каждый эндпойнт описан OpenAPI-атрибутами: `#[OA\Get|Post|Put|Delete]` на методе контроллера, `#[OA\Schema]` на Request и Response, `#[OA\Parameter]` на `IndexRequest` для query-параметров. Тег — `'{Context} {Подпроект}: {Сущность}'`.
19. Ответ — `response()->json(new {E}Response($item), 201|200)`, удаление — `response()->json(null, 204)`, список — `PaginateResource::make($service->search($search))`. Обёртку `success/data` добавляет `ApiResponseMiddleware`.
20. Маршруты: `Route::middleware([...])->prefix('{подпроект}/{context}')->as('{подпроект}.{context}.')->whereNumber('id')->group(...)`; имя — `{ресурс-во-мн.-числе}.{index|view|create|update|delete}`; URL в kebab-case, мн. число (`tariff-periods`).

## Пакеты ядра

- Ядро одно: `mps/core` + `mps/utils`, версия — из `composer.lock` проекта; версию у пользователя **не спрашивать** (в отличие от Go SDK, решение O-4). API сверять с исходниками в `vendor/mps/core` и `vendor/mps/utils` этого проекта, а не с примерами из `mps-core/*.md`.

21. Не писать то, что есть в `mps/core` / `mps/utils` (полный список — `mps-core/capabilities.md`) и в `Core/` подпроекта (`PaginatedQueryHelper`): `CRUDRepository`, `SearchDataObject`, `QueryFilters\V2`, `QueueManagerInterface` (RabbitMQ V2), `SmartCache`, `UploadManager`, логгер `for($this)`. Подробно — `mps-core/rules.md`.
22. Устаревшее API ядра (`RabbitManager::putIn`, `QueryFilters` V1, `getOrCreate`, `FileManager::upload`) в новом коде — **[ОШИБКА]**.
