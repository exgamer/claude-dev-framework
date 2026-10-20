# Теги находок (PHP): дополнение к общему каталогу

Общий каталог и правила уровней — `approaches/process/review-tags.md` (уровень находки — только в каталогах, O-24: новый код — уровень каталога, унаследованное — [ВНИМАНИЕ], `approaches/process/review.md` п. 3a); теги безопасности — `approaches/process/security.md`, «Каталог тегов безопасности». Здесь — теги, у которых механизм есть только в PHP (Laravel + mps/core); как именовать и когда заводить новый — `review.md`, «Теги».

> `workflow-takes-repository-directly` переопределён решением O-7: workflow (`Workflows/`) **может** брать репозитории любых доменов — не находка. Command/Query/Service домена с репозиторием чужого домена — тег `domain-cross-domain-access` (общий каталог).

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `request-missing-vs-null-not-distinguished` | [ОШИБКА] | Не различает «поле не прислали» и «прислали null» (`sometimes`+`nullable`, `?? null`/`isset` вместо `array_key_exists`) | `conventions.md` п. 11b |
| `update-request-optional-fields` | [ОШИБКА] | PUT: поле в `UpdateRequest` необязательно к передаче (`nullable`/`sometimes` без `present`), а сервис пишет DTO целиком — неприсланное поле затирается `null` | `conventions.md` п. 12 (O-10) |
| `array-key-unchecked` | [ОШИБКА] | `$arr['key']`/`$arr[0]`/деструктуризация по массиву из внешних данных (payload, ответ API, config, `explode`/`preg_match`, `keyBy`-карта) без проверки ключа — `Undefined array key` → `null` → `TypeError`/500 | `conventions.md` п. 11b |
| `repository-bypasses-core-query` | [ОШИБКА] | `Model::query()`/`DB::table()` в репозитории вместо `$this->getQuery()` | `conventions.md` п. 2 |
| `repository-reinvents-core-helper` | [ОШИБКА] | Самописная замена готового класса ядра/`Core/` подпроекта (`LoggerAwareTrait`, `TransactionManagerInterface`, базовые Request, фильтры не из `QueryFilters\V2` в форме (1) списка `search()`) | `mps-core/capabilities.md`; `mps-core/repository.md` |
| ~~`repository-missing-or-fail`~~ | — | не используется: `*OrFail`-методы не заводятся (`conventions.md` п. 12a) — сам `*OrFail` — `service-thin-wrapper` | — |
| `expanded-param-used` | [ОШИБКА] | Параметр `expanded`/`{E}ExpandEnum`/`hasExpand()` — клиент выбирает состав ответа; связанные данные — всегда, методом своего репозитория пачкой по id | `conventions.md`, «Модели и внешние системы» |
| `model-has-eloquent-relations` | [ОШИБКА] | `BelongsTo`/`HasMany` в модели вместо сборки через репозитории | `conventions.md`, «Модели и внешние системы» |
| `model-wrong-domain-location` | [ОШИБКА] | Модель лежит не в домене/инфраструктуре своей сущности | `structure.md` |
| ~~`service-orchestrates-foreign-repository`~~ | — | не используется (O-7): сервис домена не трогает чужой домен ни через репозиторий, ни через сервис — `domain-cross-domain-access` | — |
| `service-transaction-not-via-manager` | [ОШИБКА] | `DB::transaction()`/`$repo->transaction()` вместо `TransactionManagerInterface::run()` | `conventions.md` п. 16; `mps-core/transactions.md` |
| `octane-state-leak` | [ОШИБКА] | Под Octane/Swoole — статическое или синглтон-состояние, зависящее от запроса | `approaches/patterns/concurrency.md`, «Data race — PHP» |
| `constant-not-enum` | [ВНИМАНИЕ] / [ОШИБКА] | Ключ (атрибут, заголовок, тег кэша) или значение домена (тип, статус, провайдер) константой вместо enum; список кейсов enum константой вместо метода enum — [ВНИМАНИЕ]. Повтор значения существующего enum, константа в интерфейсе репозитория/сервиса, копия подмножества, которое уже есть методом enum или константой, — [ОШИБКА] | `conventions.md` п. 12b |
| `enum-not-enumerable` | [ВНИМАНИЕ] | Enum не реализует `MPS\Core\Enums\Enumerable` / без `EnumerableTrait` | `style.md`, «Имена»; `mps-core/enums.md` |
| `enum-contains-business-logic` | [ОШИБКА] | `match`/условие с бизнес-решением или обращение к сервису внутри enum вместо `labels()`/`values()`; предикаты-классификаторы легаси | `review-findings.md`, «Enums / Models» |
| `dto-enum-as-string` | [ОШИБКА] | Поле DTO с фиксированным набором значений хранится строкой, а не enum (сравнения через `->value`) | `conventions.md` п. 11; `mps-core/data-objects.md` |
| `dto-setter-widened-type` | [ВНИМАНИЕ] | Тип аргумента сеттера DTO шире типа свойства (`int\|string`, ручное приведение) «на случай строк» — ядро приводит само | `core/principles.md`, «Честность результата»; `mps-core/data-objects.md` |
| `dataobject-assembly-in-constructor` | [ВНИМАНИЕ] | Сложная сборка DTO из нескольких источников — в конструкторе DTO или контроллере вместо фабрики | `review-findings.md`, «DataObjects» |
| `search-filter-outside-dto` | [ОШИБКА] | Контекст-фильтр списка (здания, организация) передан в сервис отдельным аргументом и вписывается в параметры внутри него вместо поля `{E}FilterDto`, выставленного в контроллере | `conventions.md` п. 19a |
| `list-form-not-as-designed` | [ВНИМАНИЕ] | Новый список не по форме из раздела «Список» `design.md`; существующий на форме (1) — не нарушение | `conventions.md` п. 2c (P-19) |
| `command-query-form` | [ВНИМАНИЕ] | Command/Query наследует базовый класс ядра (`MPS\Core\Commands\Command`, `MPS\Core\Queries\Query`), реализует `prepare()`/`get()`/`setQueryParams()`, параметры — fluent-сеттерами с массивом или точка входа не `execute(...)` с типизированными аргументами | `mps-core/commands.md`, «Форма Command и Query» |
| `exception-not-domain-type` | [ОШИБКА] | `\RuntimeException`/`\Exception` вместо `*AppException` ядра | `conventions.md` п. 13 |
| `invariants-inline` | [ВНИМАНИЕ] | Доменные инварианты проверяются inline в сервисе/workflow, а не в `{E}DtoValidator` (или в сущности `New*`/`Restore*`, если так выбрано в `design.md`) | `conventions.md` п. 10 |
| `exception-generic-with-type` | [ВНИМАНИЕ] | `new AppException(..., AppErrorTypeEnum::X)` при готовом классе исключения ядра для этого типа | `conventions.md` п. 13 (P-12) |
| `deprecated-core-api` | [ОШИБКА] | Устаревшее API ядра в новом коде (`RabbitManager::putIn`, `QueryFilters` V1, `FileManager::upload`, `setMessagePrefix`; `getOrCreate`/`getCacheKeyByParts` — с utils ≥ 2.6.4) | `conventions.md` п. 22 |
| `dto-suffix-uppercase` | [ВНИМАНИЕ] | Суффикс `DTO` вместо `Dto` в новом PHP-коде (`BuildingDataDTO`) | `style.md`, «Имена» (P-5, P-7) |
| `di-outside-definitions` | [ОШИБКА] | Биндинг DI не в `definitions.php` из перечня `structure.md` («Биндинги DI»): `$app->singleton()`/`$this->app->bind()` в провайдере (кроме порта подпроекта с переопределением извне), биндинг домена в `config/definitions.php` | `conventions.md` п. 7 |
| `console-failure-not-reported` | [ОШИБКА] | Fail консольной команды/крона уходит только в `$this->error()`, не в Sentry | `checklist.md` п. 23; `review-findings.md`, «Ошибки, логи, Sentry» |
| `phpstan-errors` | [ОШИБКА] / [ВНИМАНИЕ] | Ошибки phpstan в файлах дифа: на уровне проекта — [ОШИБКА]; уровня 5 сверх уровня проекта (проект ниже 5) — [ВНИМАНИЕ]; `@phpstan-ignore` в коде вместо `ignoreErrors` для шума mps — [ОШИБКА] | `conventions.md` п. 23 |
| `response-double-wrap` | [ОШИБКА] | Response-класс/контроллер сам собирает `success/data`, хотя маршрут под `ApiResponseMiddleware` (`api.response`) | `conventions.md` п. 19 |
| ~~`controller-manual-response-wrapper`~~ | — | не используется — тот же паттерн, что `response-double-wrap` | — |
| `controller-response-macro` | [ОШИБКА] | Ответ через макрос/mixin фасада (`Response::ok()`, `Response::deleted()`) вместо `response()->json(...)` | `conventions.md` п. 19 |
| `api-response-not-first` | [ОШИБКА] | `api.response` не первым в middleware группы маршрутов: отказы middleware перед ним (401/403) уходят без `success/data` | `conventions.md` п. 20 |
| `route-middleware-not-aliased` | [ВНИМАНИЕ] | Свой middleware в маршруте подключён `::class`, а не алиасом из `bootstrap/app.php` | `conventions.md` п. 20 |
| `controller-missing-swagger` | [ОШИБКА] | Эндпоинт без Swagger-аннотации, без `500` в ответах или `404` не по `conventions.md` п. 18 (нет у поиска по ID или лишний у `index`/`create`) | `conventions.md` п. 18 |
| `controller-auth-via-facade` | [ОШИБКА] | `Auth::id()`/`auth()` в глубине сервиса вместо `$request->user()` в контроллере | `conventions.md` п. 19a |
| ~~`workflow-takes-repository-directly`~~ | — | не используется (O-7): workflow вправе брать репозитории напрямую; для Command/Query домена — `domain-cross-domain-access` | — |

Если ни один тег (здесь, в общем каталоге и в каталоге безопасности) не подходит — новый в этом же формате (`approaches/process/review-tags.md`, вводная).
