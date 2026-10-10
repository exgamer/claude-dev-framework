# Теги находок (PHP)

Из `php-reviewer` (раздел 5.6). Правила тегов — `approaches/process/review.md`, «Теги». Теги безопасности — колонка «Тег» в `security.md`.

> `workflow-takes-repository-directly` переопределён решением O-7: workflow (`Workflows/`) **может** брать репозитории любых доменов — не находка. Command/Query/Service домена с репозиторием чужого домена — тег `domain-cross-domain-access`, **[ОШИБКА]**.


В сохранённом файле (отчёт) колонка **«Категория»/«Уязвимость»** каждой строки — это **не архитектурный слой** (`Repository`/`Service`/`Domain`...), а **конкретный, стабильный kebab-case тег самого паттерна нарушения**. По этому тегу считаются у автора MR повторяющиеся ошибки одного типа (например, чтобы увидеть «этот разработчик регулярно делает прямые запросы в сервисе») — общий слой для этого бесполезен, а свободный текст не даёт точных совпадений.

Правила:
- kebab-case, 2–5 английских слов, называет **паттерн**, а не файл/слой (`service-direct-db-query`, не `Service` и не «прямой запрос к БД в SessionService»);
- **переиспользуй** тег из таблицы ниже (или уже использованный в этом же ревью), если находка — тот же паттерн;
- новый тег заводи только если ни один известный не подходит — коротко, конкретно, без деталей конкретного MR (детали — в `Описание`, не в теге).

Известные теги — для [БЕЗОПАСНОСТЬ] см. колонку «Тег» в 5.1; для остальных находок (из ``review-findings.md``):

| Тег | Когда использовать |
|---|---|
| `request-missing-bounds` | Число/массив в Request без верхней границы (`max:`/`between:`) — DoS через тело запроса |
| `request-queries-db` | Request ходит в БД: `exists:`/`unique:`, `Rule::exists/unique`, своё правило или замыкание с моделью/`DB::`/репозиторием, запрос в `prepareForValidation`/`withValidator`/`after` (P-17) |
| `request-business-rule-in-validation` | Бизнес-правило (не enum допустимых значений) проверяется в Request вместо сервиса |
| `request-missing-vs-null-not-distinguished` | Не различает «поле не прислали» и «прислали null» (`sometimes`+`nullable`, `?? null` вместо `array_key_exists`) |
| `update-request-optional-fields` | PUT: поле в `UpdateRequest` необязательно к передаче (`nullable`/`sometimes` без `present`), а сервис пишет DTO целиком — неприсланное поле затирается `null` |
| `repository-bypasses-core-query` | `Model::query()`/`DB::table()` вместо `$this->getQuery()` |
| `write-array-instead-of-dto` | Данные на запись передаются массивом вместо DTO/типизированных аргументов: контроллер → сервис/workflow (`$request->validated()`, `->all()`), сервис/Command/workflow → репозиторий (`create($data)`, `updateById($id, $dto->toArray…())`, литерал `['col' => $v]`) (`conventions.md` п. 11a, P-18) |
| ~~`repository-missing-or-fail`~~ | не используется: `*OrFail`-методы не заводятся (`conventions.md` п. 12a) — сам `*OrFail` находка с тегом `service-thin-wrapper` |
| `repository-returns-mapped-structure` | Repository делает `keyBy`/`map`/сборку структур вместо сырых данных |
| `expanded-param-used` | Параметр `expanded`/`{E}ExpandEnum`/`hasExpand()` — клиент выбирает состав ответа; связанные данные — всегда, методом своего репозитория пачкой по id (`conventions.md`, «Модели и внешние системы») |
| `repository-cross-domain-access` | Repository пишет в таблицу чужого домена, вызывает его репозиторий или несёт его бизнес-логику; read-only подзапрос (`EXISTS`/`JOIN`) с комментарием, чья таблица, — не находка (`architecture/cross-domain.md`) |
| `repository-reinvents-core-helper` | Самописная обёртка вместо готового `QueryFilters\V2`/`PaginatedQueryHelper`/`LogAwareTrait` из core |
| `service-direct-db-query` | Прямой `DB::`/`Model::where` в сервисе вместо репозитория |
| ~~`service-orchestrates-foreign-repository`~~ | не используется (O-7): сервис домена не трогает чужой домен ни через репозиторий, ни через сервис — тег `domain-cross-domain-access` |
| `workflow-without-transaction` | Workflow пишет без `TransactionManagerInterface::run()` и без причины в `design.md` (O-8) |
| `multi-write-without-transaction` | Service/Command домена делает несколько связанных записей без общей транзакции |
| `service-transaction-not-via-manager` | `DB::transaction()`/`$repo->transaction()` вместо `TransactionManagerInterface` |
| `service-method-name-mismatch` | Имя метода не соответствует тому, что он реально делает |
| `service-status-branching-duplicated` | Ветвление по статусам (`if`/`elseif`) продублировано в нескольких методах вместо state machine |
| `service-side-effect-timing-wrong` | Побочный эффект (Storage/HTTP/очередь) не снаружи транзакции или до записи вместо после |
| `controller-manual-response-wrapper` | Ручная обёртка `['success' => true, 'data' => ...]` вместо `JsonResponse` ядра |
| `response-double-wrap` | Response-класс/контроллер сам собирает `success/data`, хотя маршрут под `ApiResponseMiddleware` (`superAppApi`) |
| `service-thin-wrapper` | Метод-обёртка над одной проверкой/вызовом в сервисе или репозитории (`findByIdOrFail`/любой `*OrFail`, `assertValid`, внутренний `getById` → свой репозиторий); публичный `findById(): ?Model` для точки входа — не находка |
| `constant-not-enum` | Значение-ключ константой класса/интерфейса вместо enum |
| `search-filter-outside-dto` | Фильтр поиска передан отдельным аргументом и вписывается в параметры внутри сервиса |
| `list-without-default-sort` | Список без `$sortAttributes` и сортировки по умолчанию |
| `request-filter-not-applied` | Поле валидируется в `IndexRequest`, но не применяется в `filterSearch` |
| `request-access-check` | Проверка доступа (здание/организация пользователя) в правилах FormRequest вместо middleware |
| `route-middleware-not-aliased` | Свой middleware в маршруте подключён `::class`, а не алиасом из `bootstrap/app.php` |
| `access-check-outside-middleware` | Права/контекст (роль или право на операцию, здания, организация) проверяются или вычисляются в контроллере или сервисе, а не в middleware на маршруте → атрибуты запроса → `RequestHelper` (для Go — тот же тег) |
| `controller-response-macro` | Ответ через макрос/mixin фасада (`Response::ok()`, `Response::deleted()`) вместо `response()->json(...)` |
| `controller-missing-swagger` | Эндпоинт без Swagger-аннотации или без `404`/`500` в ответах |
| `controller-auth-via-facade` | `Auth::id()` в глубине сервиса вместо `request()->user()` в контроллере |
| ~~`workflow-takes-repository-directly`~~ | не используется (O-7): workflow вправе брать репозитории напрямую; для Command/Query домена — `domain-cross-domain-access` |
| `workflow-trusts-transport-request` | Workflow или Command домена не проверяет доменные инварианты (`{E}DtoValidator`, P-8), полагаясь на Request, — ломается при вызове из консоли/очереди |
| `domain-cross-domain-access` | Код в `Domains/` (Service, Command, Query) обращается к чужому домену — модель, репозиторий или сервис; место такого сценария — workflow (O-7) |
| `dataobject-assembly-in-constructor` | Сложная сборка DTO из нескольких источников — в конструкторе/контроллере вместо фабрики |
| `enum-contains-business-logic` | `match`/условие с бизнес-решением внутри enum вместо `labels()`/`values()` |
| `model-has-eloquent-relations` | `BelongsTo`/`HasMany` в модели вместо сборки через репозитории |
| `model-wrong-domain-location` | Модель лежит не в домене/инфраструктуре своей сущности |
| `gateway-named-as-service` | Клиент внешней системы называется `*Service` вместо `*Gateway` |
| `gateway-config-via-global` | `app()`/`config()` внутри метода вместо инъекции в конструктор |
| `gateway-missing-config-validation` | Отсутствующий обязательный конфиг даёт молчаливый дефолт вместо `InvalidConfigurationAppException` |
| `error-swallowed-silently` | `catch` без rethrow и без `AppException` с контекстом |
| `exception-not-domain-type` | `\RuntimeException`/`\Exception` вместо `*AppException` ядра |
| `console-failure-not-reported` | Fail консольной команды/крона уходит только в `$this->error()`, не в Sentry |
| `n-plus-one-query` | Запрос к БД внутри цикла вместо пачки по `ids` |
| `bulk-operation-not-chunked` | Большая выборка/массовая операция без `chunk()`/`cursor()`/батчей |
| `in-memory-state-not-shared` | Кэш/дедупликатор/счётчик в памяти процесса — ломается при 2+ инстансах |
| `enum-list-drift` | Новое значение enum не отражено во всех местах, где перечислен старый набор (`in:`, swagger `enum:`, словари фронта, мапперы/allowlist/`match` по этому enum) |
| `legacy-pattern-copied` | Из легаси или соседнего кода перенесена реализация вопреки правилу стека (слой проверки, форма класса, фильтр, валидация) — в коде или в артефакте (`threats.md`, `design.md`) (`approaches/process/legacy-refactor.md`, O-17) |
| `scope-param-defaults-unrestricted` | Параметр скоупа в сигнатуре сервиса/репозитория с умолчанием `= null` («без ограничения») — fail-open для будущих вызовов (`security.md`, `tenant-scope-missing`) |
| `refactor-toggle` | Рефактор/перенос: старая и новая реализация выбираются флагом (конфиг/env/БД, DI по настройке, процент трафика, заголовок, shadow/dual-write) или контракт изменён под старым URL/событием без версии — вместо сохранённого контракта или новой версии (`approaches/process/legacy-refactor.md`, O-19) |
| `deploy-risk-flag-enabled` | Раскомментированный крон/включённый флаг — риск первого прогона на накопленных данных |
| `mr-hygiene-lockfile-drift` | `composer.lock` не обновлён вместе с `composer.json`, либо сгенерированные файлы (`.php-cs-fixer.cache`, `storage/api-docs/*.json`) уехали в MR |
| `ai-style-comment` | Комментарий-пересказ кода построчно, сгенерированный ИИ |
| `enum-not-enumerable` | Enum не реализует `MPS\Core\Enums\Enumerable` / без `EnumerableTrait` |
| `dto-enum-as-string` | Поле DTO с фиксированным набором значений хранится строкой, а не enum (сравнения через `->value`) |
| `dto-setter-widened-type` | Тип аргумента сеттера DTO шире типа свойства (`int\|string`, ручное приведение) «на случай строк» — ядро приводит само |
| `comment-restates-name` | Описание метода/класса пересказывает его имя («Неудалённые здания по набору id» над `findAliveByIds`) |

Если ни один тег не подходит — придумай новый в этом же формате.

---
