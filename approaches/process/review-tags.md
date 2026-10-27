# Каталог тегов находок (общий для стеков)

Уровень находки с тегом записан **только здесь** (O-24): в файлах правил, памятках и `review-findings.md` — тег без уровня, со ссылкой сюда. Как называть тег и когда заводить новый — `review.md`, «Теги».

- **Новый код** (написан или переписан задачей) — уровень из колонки «Уровень». **Унаследованное** (было до задачи, в диффе нет) — **[ВНИМАНИЕ]** «унаследовано» (`review.md`, «Порядок», п. 3a); безопасность — всегда по каталогу безопасности.
- Несколько уровней в колонке — по случаю, названному в «Суть»; остальные случаи тега — по первому уровню.
- Пометки памяток (`stacks/<stack>/checklist.md`): **О** — [ОШИБКА], **В** — [ВНИМАНИЕ], **И** — [ИНФО]; они совпадают с каталогом, при расхождении главнее каталог.
- Дополнения стеков: `stacks/php/review-tags.md`, `stacks/go/review-tags.md`. Теги безопасности и их уровни — `security.md`, «Каталог тегов безопасности».
- У правила без тега уровня нет (O-24): для него заводится тег здесь или в стековом каталоге, в правиле — тег. Находке не подошёл ни один тег — новый тег по `review.md`, «Теги», уровень — по нарушенному правилу, строка в `rules-log.md` с предложением «фреймворк: каталог тегов».

## Слои и место кода

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `file-wrong-location` | [ОШИБКА] | Файл не по пути и не под именем из `structure.md` стека: тип компонента не в своей папке, `Infrastructure/` без типа (PHP), тест внутри подпроекта (PHP), модуль сборки workflow не тот, `*_command.go` в `workflows/`, адаптер не по имени провайдера (Go); новый файл типа компонента, которого нет в составе своего слоя (Go — «Состав» слоя и таблица `review-checks.md`, «Проверка расположения файла»; PHP — «Дерево подпроекта»; вспомогательный файл Go по `conventions.md` п. 16 и файл, тип которого не определяется однозначно, — не находка), в т. ч. когда так записано в `design.md` или мере `threats.md` (`routes.go` с группами и middleware в bootstrap) | `stacks/<stack>/structure.md`; Go — `review-checks.md`, «Проверка расположения файла»; `approaches/process/review.md`, «Порядок», п. 1 |
| `service-direct-db-query` | [ОШИБКА] | Запрос к хранилищу (БД, кэш, брокер) вне Infrastructure: в сервисе, Command/Query, workflow, хендлере, consumer; запрос или кусок условия, собранный в сервисе/workflow | `architecture/layers.md`, «Правила слоёв» |
| `repository-multi-query` | [ОШИБКА] | Публичный метод репозитория выполняет больше одного запроса («заблокировать и удалить», «прочитать и сохранить»); пагинатор и пакетный `upsert` — один запрос | `architecture/layers.md`, «Правила слоёв» |
| `query-split-into-pieces` | [ВНИМАНИЕ] | Запрос репозитория собран из функций-кусков, колонки — константой, подзапрос в 4+ методах, запрос-монстр на 3+ подзапроса по чужим таблицам вместо сборки в сервисе/workflow | `architecture/layers.md`, «Правила слоёв» |
| `repository-returns-mapped-structure` | [ОШИБКА] | Репозиторий решает по значениям или трансформирует данные под потребителя (`keyBy`, `map`, сборка структур) вместо сырых данных | `architecture/layers.md`, «Правила слоёв» |
| `repository-cross-domain-access` | [ОШИБКА] | Репозиторий пишет в таблицу чужого домена, вызывает его репозиторий или несёт его логику; read-only подзапрос с комментарием, чья таблица, — не находка | `architecture/cross-domain.md` |
| `service-thin-wrapper` | [ВНИМАНИЕ] | Метод-обёртка над одной проверкой или вызовом (`*OrFail`, `assertValid`, внутренний `getById` → свой репозиторий); публичный `findById` для точки входа — не находка | `architecture/layers.md`, «Правила слоёв» |
| `domain-cross-domain-access` | [ОШИБКА] | Service, Command/Query домена обращается к чужому домену (модель, репозиторий, сервис, свой интерфейс на чужой сервис) — место сценария — workflow | `architecture/layers.md`, «Зависимости» (O-7) |
| `domain-module-cross-import` | [ОШИБКА] | Модуль домена импортирует соседний модуль своего домена (сущность, enum, DTO, сервис) вместо интерфейса у потребителя (O-6) и `Common` домена | `architecture/layers.md`, «Зависимости» |
| `workflow-calls-workflow` | [ОШИБКА] | Workflow вызывает другой workflow (через конструктор, `app()`, `new`) или зависит от него; общая логика — в сервисы/Command/Query доменов | `architecture/layers.md`, «Зависимости» |
| `workflow-duplicates-domain-port` | [ВНИМАНИЕ] | Workflow объявляет свой порт внешней системы, хотя порт уже есть в домене; порт workflow со вторым потребителем — в домен | `architecture/layers.md`, «Зависимости» |
| `workflow-trusts-transport-request` | [ВНИМАНИЕ] | Workflow или Command не проверяет значения входа (валидатор DTO) и правила состояния (модель), полагаясь на Request/биндинг — ломается при вызове из консоли и очереди | `architecture/layers.md`, «Меры по слоям» (O-18) |
| `invariants-inline` | [ВНИМАНИЕ] | Проверка значений DTO или правило состояния/перехода inline в Service, Command, Workflow вместо `{E}DtoValidator` / `{entity}_validator.go` или метода модели (`CanArchive()`) | `regulations/entity.md` (O-26); PHP — `conventions.md` п. 10 |
| `status-in-update-request` | [ОШИБКА] | Request или биндинг изменения (PUT/PATCH) принимает `status` или другой флаг состояния; общий эндпоинт или Command «сменить статус» (`ChangeStatus`, `setStatus`) со статусом параметром вместо эндпоинта и Command перехода | `regulations/entity.md`, «Переход состояния» (O-28); `regulations/rest-api.md`, «Методы» |
| `duplicated-enum` | [ОШИБКА] | Копия понятия другого домена (тот же набор значений под другим именем) или импорт чужого домена вместо переноса в `Common` | `architecture/layers.md`, «Общий бизнес-словарь» |
| `common-with-logic` | [ОШИБКА] | В `Common` — зависимости в конструкторе, ввод-вывод, DI, сущность, сервис, процесс | `architecture/layers.md`, «Общий бизнес-словарь» |
| `write-array-instead-of-dto` | [ОШИБКА] | Данные на запись между слоями — массивом/картой колонок вместо DTO или типизированных аргументов (PHP `validated()` в сервис, Go `map[string]any` с колонками в домене) | `core/style.md`, «Данные между слоями»; PHP — `conventions.md` п. 11a |
| `scope-param-defaults-unrestricted` | [ОШИБКА] | Скоуп владельца с умолчанием «без ограничения»: параметр `= null`/`[]uint` с `nil`, поле фильтра без сеттера читается как «все», ключ скоупа необязателен. Достижимо вводом клиента — [БЕЗОПАСНОСТЬ] `tenant-scope-missing`; без ввода — не [БЕЗОПАСНОСТЬ], а этот тег с его уровнем | `architecture/layers.md`, «Меры по слоям»; Go — `conventions.md` п. 7a, PHP — п. 19a |
| `request-queries-db` | [ОШИБКА] | Валидация входа ходит в хранилище (`exists:`/`unique:`, свои правила с моделью/репозиторием, запрос в хуках валидации) — в коде и в мерах `threats.md`/`design.md` | `architecture/layers.md`, «Меры по слоям» (P-17) |
| `request-business-rule-in-validation` | [ОШИБКА] | Бизнес-правило (не набор допустимых значений) проверяется в валидации входа вместо сервиса | `regulations/rest-api.md`, «Валидация входа»; `architecture/layers.md`, «Меры по слоям» |
| `request-missing-bounds` | [ОШИБКА] | Число, строка или массив во входе без верхней границы (DoS через тело запроса); `id` без `> 0` | `regulations/rest-api.md`, «Валидация входа» |
| `request-body-not-strict` | [ОШИБКА] / [ВНИМАНИЕ] | Маршрут с телом (`POST`/`PUT`/`PATCH`) принимает не только JSON или разбирает тело не строго (хвост, второй объект, повтор ключа): тело формой или query уходит мимо проверки middleware и полноты PUT (O-10). Go: биндинг по `Content-Type` (`binding.Default`, `ShouldBind`, `ValidateRequestBody` SDK) без `RequireJSONMiddleware` на маршруте с телом. Существующий ответ 422 вместо 413 на тело больше предела — [ВНИМАНИЕ] | `architecture/layers.md`, «Принадлежность записи скоупу — два случая»; Go — `stacks/go/structure.md`, «Entrypoint Layer» |
| `missing-body-limit` | [ОШИБКА] | Тело запроса читается без предела размера (middleware, хендлер, `io.ReadAll(r.Body)`); больше предела — 413 | `architecture/layers.md`, «Принадлежность записи скоупу — два случая»; `regulations/rest-api.md`, «Статусы» |
| `url-not-by-scheme` | [ВНИМАНИЕ] | Путь нового эндпойнта не по схеме `/api/<context>/<version>/<domain>/<module>/<entity>/<action?>`: без версии или домена, глагол вместо `action`, модуль повторяет сущность, сущность в единственном числе; существующий путь — «унаследовано» | `regulations/rest-api.md`, «URL» |
| `swagger-status-mismatch` | [ВНИМАНИЕ] | Swagger эндпойнта не совпадает с тем, что он отдаёт: пропущенный код (`500`, 400/413/415 по телу, 401/403 от middleware маршрута или группы — PHP `403` у `permission:…`, 404, 422) или лишний (PHP — `404` у `index`/`create`, не по `stacks/php/conventions.md` п. 18). Эндпойнт без Swagger вовсе — PHP `controller-missing-swagger` | `regulations/rest-api.md`, «Статусы»; Go — `stacks/go/go-sdk/entrypoint/http.md`; PHP — `stacks/php/conventions.md` п. 18 |
| `request-access-check` | [ОШИБКА] | Проверка доступа (здание, организация пользователя) в валидации входа вместо middleware | `architecture/layers.md`, «Валидация, доступ и фильтр данных» |
| `access-check-outside-middleware` | [ОШИБКА] | Роль, право или контекст доступа проверяются/вычисляются в контроллере, хендлере или домене, а не middleware на маршруте. Не находка — повторная сверка id владельца из тела со скоупом в хендлере создания Go (`architecture/layers.md`, «Принадлежность записи скоупу — два случая», случай 1) | `architecture/layers.md`, «Роли и права на операцию» |
| `adapter-named-not-by-role` | [ОШИБКА] | Порт/адаптер внешней системы назван не по роли (`*Gateway`, `*Service`, `*Client`, имя системы в имени порта) вместо `…Repository` / `…Notifier`, `…Publisher`, `…Sender` | `architecture/integrations.md` |
| `adapter-config-via-global` | [ОШИБКА] | Клиент или конфиг адаптера берётся глобально внутри метода (`app()`/`config()`, создание клиента в репозитории) вместо инъекции в конструктор | `architecture/integrations.md` |
| `adapter-missing-config-validation` | [ОШИБКА] | Отсутствующая обязательная настройка даёт молчаливый дефолт вместо отказа на старте/исключения конфигурации | `regulations/service-requirements.md`, «Конфиг» |
| `integration-without-dto` | [ВНИМАНИЕ] | Ответ или тело запроса внешнего API без DTO интеграции (поле ответа по месту, тело литералом массива/`map`); с непроверенным ключом — [ОШИБКА] `array-key-unchecked` (решение сверка 2026-10-20) | `regulations/contracts.md`; PHP — `conventions.md` п. 11b |
| `service-method-name-mismatch` | [ОШИБКА] | Имя функции, метода, типа или поля обещает не то, что делает код (`Enqueue`/`dispatch` без очереди, `update()` с select + create + restore) | Go — `conventions.md` п. 20; PHP — `review-findings.md`, «Service» |
| ~~`service-status-branching-duplicated`~~ | — | не используется (O-25, O-26): повтор шагов в Service/Command/Workflow — не находка; условие допустимости статуса вне модели — `invariants-inline` | — |
| `error-swallowed-silently` | [ОШИБКА] | Ошибка потеряна: `_ =` для ошибки, пустой `catch`, `catch` с `return null`/дефолтом без rethrow, без исключения с контекстом и без отправки в трекер | Go — `conventions.md` п. 8; PHP — `conventions.md` п. 17a, `checklist.md` п. 23 |
| `error-not-reported` | [ВНИМАНИЕ] | Намеренно не проброшенная ошибка (запись сохранена, сбой побочного эффекта) уходит только в лог (`logger.*`, `Log::*`), а не в трекер (Go `errorreporter`, PHP `ExceptionHandler::report()`) | Go — `conventions.md` п. 19; PHP — `conventions.md` п. 17a |
| `console-failure-not-reported` | [ОШИБКА] | Сбой консольной команды или крона уходит только в её вывод (PHP `$this->error()`), не в трекер ошибок (Go `errorreporter`, PHP `ExceptionHandler::report()`) | `regulations/service-requirements.md`, пункт «Ошибки»; PHP — `checklist.md` п. 23, `review-findings.md`, «Ошибки, логи, Sentry»; Go — `checklist.md` п. 21a, `conventions.md` п. 19 |
| `layer-dependency-forbidden` | [ОШИБКА] | Зависимость по запрещённому направлению (`Domain → Infrastructure \| Entrypoint \| Workflow`, `Workflow → Entrypoint`, `Infrastructure → Entrypoint`, `Entrypoint → Bootstrap`, `Context A → Context B`), у случая которой нет своего тега (`domain-cross-domain-access`, `workflow-calls-workflow`, `service-direct-db-query` — главнее) | `architecture/layers.md`, «Направление зависимостей» |
| `di-resolve-in-business-code` | [ОШИБКА] | Резолв из контейнера внутри бизнес-кода (`di.Resolve`, `app()`, `resolve()`) вместо конструктора; исключение — места без конструктора по правилам стека; Go — контейнер вне ядер, `app.go` и `module.go` модулей bootstrap | `architecture/layers.md`, «Правила слоёв, общие для стеков»; Go — `stacks/go/structure.md`, «Bootstrap / Application Layer» |
| `workflow-single-domain` | [ВНИМАНИЕ] | Однодоменная операция вынесена в workflow — место ей в Command/Query домена | `architecture/layers.md`, «Зависимости» |
| `hidden-async-in-service` | [ОШИБКА] | Скрытая горутина/`dispatch` внутри сервиса, если асинхронность не его контракт: асинхронность решает вызывающий (workflow, entrypoint, runner) | `approaches/patterns/async.md`; Go — `stacks/go/conventions.md` п. 18 |
| `unverified-workaround` | [ВНИМАНИЕ] | Обход поведения ядра/SDK/библиотеки по непроверенному предположению (не сверен с исходником версии проекта и не подтверждён прогоном); частный случай PHP — `dto-setter-widened-type` | `core/principles.md`, «Честность результата» |
| `api-not-in-version` | [ОШИБКА] | Вызов класса, метода или опции SDK/ядра, которых нет в версии проекта (`capabilities.md`, `reference/` версии) | `approaches/process/review.md`, «Порядок», п. 7 |

## Транзакции, конкурентность, outbox

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `multi-write-without-transaction` | [ОШИБКА] | Больше одного модифицирующего запроса в сценарии (workflow, сервис, Command) без общей транзакции | `approaches/patterns/transactions.md` (O-8) |
| `needless-transaction` | [ВНИМАНИЕ] | Своя транзакция вокруг одного модифицирующего запроса без блокировки по `concurrency.md` (решение O-8, пересмотр 2026-10-20) | `approaches/patterns/transactions.md` (O-8) |
| `long-transaction` | [ОШИБКА] | В транзакции внешний HTTP, публикация, файлы, `sleep` | `approaches/patterns/transactions.md`; `performance.md` |
| `service-side-effect-timing-wrong` | [ОШИБКА] | Побочный эффект (очередь, HTTP, файлы, сброс кэша) внутри транзакции или до записи вместо после коммита | `approaches/patterns/transactions.md` |
| `race-on-related-state` | [ОШИБКА] | Состояние связанной записи проверено, а гонка между проверкой и записью не закрыта: нет `FOR SHARE` в транзакции и нет принятого риска с ответом пользователя | `approaches/patterns/concurrency.md`, «Race condition — решения» |
| `race-risk-accepted-silently` | [ОШИБКА] | Риск гонки принят без ответа пользователя (комментарий «принятый риск», вопроса в «Открытых вопросах» `design.md` нет) | `approaches/patterns/concurrency.md`, «Принятие риска» |
| `race-on-counter` | [ОШИБКА] | Счётчик/лимит «прочитать — проверить — записать» вместо атомарного условного `UPDATE` | `approaches/patterns/concurrency.md` |
| `race-on-uniqueness` | [ОШИБКА] | Уникальность только проверкой в коде, без уникального индекса и обработки его нарушения | `approaches/patterns/concurrency.md` |
| `race-on-status` | [ОШИБКА] | Смена статуса без условия на текущий статус и без блокировки; финальный статус перезаписывается | `approaches/patterns/concurrency.md`; `payments.md` |
| `missing-idempotency` | [ОШИБКА] | Повторная доставка или колбэк создаёт вторую запись: нет ключа идемпотентности с уникальным индексом | `approaches/patterns/idempotency.md`; `concurrency.md`; `payments.md` |
| `lost-update` | [ОШИБКА] | Конкурентное редактирование, отмеченное в «Конкурентность» `design.md`, без оптимистической блокировки | `approaches/patterns/concurrency.md` |
| `double-processing` | [ОШИБКА] | Несколько воркеров на одну очередь записей без `FOR UPDATE SKIP LOCKED` / статуса «в обработке» | `approaches/patterns/concurrency.md` |
| `cron-overlap` | [ОШИБКА] | Крон на нескольких инстансах без блокировки запуска | `approaches/patterns/concurrency.md` |
| `unsafe-distributed-lock` | [ОШИБКА] | Распределённая блокировка без уникального токена, TTL или снятия своим токеном | `approaches/patterns/concurrency.md` |
| `in-process-lock-for-distributed` | [ОШИБКА] | Блокировка в памяти процесса (mutex) как защита от гонок между запросами/инстансами | `approaches/patterns/concurrency.md` |
| `in-memory-state-not-shared` | [ОШИБКА] | Кэш, дедупликатор, счётчик в памяти процесса — ломается при 2+ инстансах | `approaches/patterns/performance.md`, «Память и CPU»; `concurrency.md` |
| `cache-stampede` | [ВНИМАНИЕ] | Горячий ключ кэша без защиты от одновременного перезаполнения | `approaches/patterns/concurrency.md` |
| `side-effect-without-outbox` | [ОШИБКА] | Побочный эффект, которому нужна гарантия доставки (ТЗ, сверка 2026-10-20; платёжный путь — всегда), — fire-and-forget после записи вместо outbox | `approaches/patterns/outbox.md`; `payments.md` |
| `status-without-consumer` | [ОШИБКА] | Новый статус/значение без потребителя (крон, consumer, переход, ручной разбор с описанной выборкой/алертом) — тупик | `approaches/process/impact-analysis.md`; `outbox.md`, «Форма» |
| `payment-without-parent` | [ОШИБКА] | Платёж без связанной сущности (`0` или пустой id родительской записи) не отсекается до записи | `approaches/patterns/payments.md` |

## Производительность, кэш, списки

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `n-plus-one-query` | [ОШИБКА] | Запрос к БД в цикле вместо пачки по набору id | `approaches/patterns/performance.md`, «База данных» |
| `unbounded-query` | [ОШИБКА] | Список без пагинации и верхней границы `per_page`; выборка «всего» не по маленькому справочнику | `approaches/patterns/performance.md`, «База данных» |
| `missing-index` | [ОШИБКА] | Нет индекса под фильтр, сортировку, JOIN, внешний ключ, уникальность в той же миграции | `approaches/patterns/performance.md`; `migrations.md` |
| `query-without-timeout` | [ОШИБКА] | Запрос к БД/Redis без таймаута (механизм стека) | `approaches/patterns/performance.md`, «База данных» |
| `bulk-operation-not-chunked` | [ОШИБКА] | Массовая операция или большая выборка не пачками (`chunk`/`cursor`/батч, `upsert`) | `approaches/patterns/performance.md`, «База данных» |
| `load-all-into-memory` | [ОШИБКА] | Большая выборка или файл целиком в память вместо потока/частей | `approaches/patterns/performance.md`, «Память и CPU» |
| `heavy-sync-in-request` | [ОШИБКА] | Тяжёлая работа (выгрузка, рассылка, пересчёт) синхронно в HTTP-запросе | `approaches/patterns/performance.md`, «Внешние вызовы и асинхронность» |
| `external-call-without-timeout` | [ОШИБКА] | Внешний вызов без таймаута; ретрай неидемпотентного или постоянной ошибки | `approaches/patterns/performance.md`, «Внешние вызовы и асинхронность» |
| `external-call-in-loop` | [ВНИМАНИЕ] | Внешний вызов в цикле по одному вместо батч-метода или ограниченного параллелизма | `approaches/patterns/performance.md`, «Внешние вызовы и асинхронность» |
| `consumer-unbounded` | [ВНИМАНИЕ] | Consumer без `prefetch`/числа воркеров по нагрузке | `approaches/patterns/performance.md`; `async.md` |
| `query-not-explained` | [ВНИМАНИЕ] | Новый тяжёлый запрос не проверен `EXPLAIN` на реалистичных данных | `approaches/patterns/performance.md`, «База данных» |
| `aggregation-in-memory` | [ВНИМАНИЕ] | Агрегаты, фильтры, сортировки загрузкой в память вместо БД | `approaches/patterns/performance.md`, «База данных» |
| `select-heavy-columns` | [ВНИМАНИЕ] | В списке по большой таблице тянутся тяжёлые JSON/text-колонки | `approaches/patterns/performance.md`, «База данных» |
| `expensive-count` | [ВНИМАНИЕ] | `COUNT(*)` по большой таблице на каждый запрос списка без осознанного решения | `approaches/patterns/performance.md`, «База данных» |
| `deep-offset-pagination` | [ВНИМАНИЕ] | Глубокий `OFFSET` на частом эндпойнте вместо keyset | `approaches/patterns/performance.md`, «База данных» |
| `repeated-work-in-loop` | [ВНИМАНИЕ] | Повторная тяжёлая работа в цикле (парсинг, регулярка, конфиг, рефлексия) | `approaches/patterns/performance.md`, «Память и CPU» |
| `heavy-logging-hot-path` | [ВНИМАНИЕ] | Логирование больших payload в горячем пути; ПДн и секреты в логах — [БЕЗОПАСНОСТЬ] `sensitive-data-in-logs` | `approaches/patterns/performance.md`, «Память и CPU» |
| `cache-without-invalidation` | [ВНИМАНИЕ] | Кэш без инвалидации при записи или без TTL | `approaches/patterns/cache.md`; `performance.md`, «Кэш» |
| `cache-key-incomplete` | [ВНИМАНИЕ] | Ключ кэша без префикса сервиса или без параметра, от которого зависит значение | `approaches/patterns/cache.md`; `performance.md`, «Кэш» |
| `cache-stores-errors` | [ВНИМАНИЕ] / [ОШИБКА] | Кэшируется ошибка внешней системы; «не найдено» — без осознанного короткого TTL. Платёжный путь (ответы шлюза) — [ОШИБКА] | `approaches/patterns/performance.md`, «Кэш»; `payments.md` |
| `pagination-format` | [ОШИБКА] | Блок `pagination` списка не в формате Go SDK (свои ключи, формат фреймворка) | `regulations/rest-api.md`, «Формат ответа» |
| `list-without-default-sort` | [ВНИМАНИЕ] | Список без явной стабильной сортировки по умолчанию (ключ или дата + `id`) | `regulations/rest-api.md`, «Валидация входа» |
| `list-params-contract` | [ВНИМАНИЕ] / [ОШИБКА] | Параметры списка не по контракту: умолчания (`page` 1, `per_page` 30), отрицательный `page` или больше 10000 не дают 422, сортировка не enum (нет верхней границы — `request-missing-bounds`, `unbounded-query`). `page` 0 или не переданный не приведён к 1 при разборе запроса, до расчёта смещения (`(page − 1) × per_page` по беззнаковому переполняется) — [ОШИБКА] | `regulations/rest-api.md`, «Валидация входа» |
| `list-filters-conflict` | [ВНИМАНИЕ] | Два фильтра списка по одной колонке: умолчание производного глушит явный фильтр (пустой список) или противоречивая явная пара не даёт 422; правило сочетания не описано в Swagger; умолчание производного — `default` в схеме OpenAPI, а не текстом в описании параметра | `regulations/rest-api.md`, «Валидация входа» |
| `request-filter-not-applied` | [ВНИМАНИЕ] | Поле запроса списка валидируется, но не попадает в фильтр или не применяется условием (и наоборот) | `regulations/rest-api.md`, «Валидация входа» |

## Связи, миграции, легаси, MR

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `impact-unhandled-usage` | [ОШИБКА] | Изменённый символ, роут, событие, таблица или enum, использование которого не учтено (нет в «Связях» `design.md`, не изменено) | `approaches/process/impact-analysis.md`; `review.md`, «Порядок», п. 8 |
| `contract-change-unasked` | [ОШИБКА] / [ВНИМАНИЕ] | Изменён контракт (API, событие, таблица или формат кэша для других) без ответа пользователя о совместимости в «Контрактах» `design.md`; ревью без `design.md` — [ВНИМАНИЕ] «проверить, согласовано ли изменение контракта» | `approaches/process/impact-analysis.md`, «Обратная совместимость» (O-29); `review.md`, «Порядок», п. 8 |
| `enum-list-drift` | [ОШИБКА] | Новое значение enum/статуса/allowlist не отражено во всех местах старого набора (валидации, Swagger `enum`, словари фронта, мапперы, `switch`/`match`) | `approaches/process/impact-analysis.md`; `review.md`, «За пределами дифа», п. 3 |
| `schema-not-approved` | [ОШИБКА] / [ВНИМАНИЕ] | Изменение структуры БД нет в «Схеме БД» `design.md` или нет согласования; ревью без `design.md` — [ВНИМАНИЕ] «проверить согласование схемы» | `regulations/technical-approval.md`; `review.md`, «За пределами дифа», п. 4a |
| `legacy-pattern-copied` | [ОШИБКА] | Из легаси или соседнего кода перенесена реализация вопреки правилу стека — в коде или в мерах `threats.md`/`design.md` | `approaches/process/legacy-refactor.md` (O-17) |
| `refactor-toggle` | [ОШИБКА] | Рефактор/перенос с переключателем старой и новой реализации или новый контракт под старым адресом без версии | `approaches/process/legacy-refactor.md` (O-19) |
| `deploy-risk-flag-enabled` | [ОШИБКА] | Раскомментированный крон, включённый флаг, снятый `if (false)` — первый прогон на накопленных данных | `review.md`, «За пределами дифа», п. 7 |
| `mr-hygiene-lockfile-drift` | [ВНИМАНИЕ] | Lock-файл не обновлён вместе с манифестом, сгенерированные файлы уехали в MR | `review.md`, «За пределами дифа», п. 7 |
| `outdated-runtime` | [ВНИМАНИЕ] | Дифф вводит или меняет версию языка, рантайма или базового образа на неподдерживаемую или не на последний патч ветки | `review.md`, «За пределами дифа», п. 7 |
| `mr-hygiene` | [ВНИМАНИЕ] | Пункт гигиены MR без своего тега: неиспользуемые импорты, конфликт-маркеры, Swagger не перегенерирован после правки роутов/DTO, название MR или коммиты не по `regulations/git.md`, сценарий без теста при живых тестах в проекте | `review.md`, «За пределами дифа», п. 7 |
| `debug-output-left` | [ОШИБКА] | Отладочный вывод в коде: PHP — `dd()`, `dump()`, `var_dump()`, `ray()`, `->dd()`/`->dump()` у коллекции или запроса; Go — `fmt.Print*`, `println`/`print` вместо логгера | PHP — `checklist.md` п. 52; Go — `checklist.md` п. 52 (`forbidigo`) |
| `design-not-matching-code` | [ИНФО] | Код расходится с `design.md` задачи (имена, состав файлов, поведение, контракт) без строки об отступлении в `rules-log.md`; схема БД — `schema-not-approved` | `approaches/process/review.md`, «Порядок», п. 1 |
| `bugfix-no-failing-test` | [ОШИБКА] | Баг-фикс без теста бага, или тест не падал на коде до правки (проверка на копии до правки — «проходит»), или проверяет реализацию правки, а не поведение из симптома | `approaches/process/bugfix.md`, «Ревью баг-фикса» |
| `bugfix-symptom-patched` | [ОШИБКА] | Баг-фикс закрывает симптом проверкой или обходом в вызывающем коде, а неверное место (причина) осталось | `approaches/process/bugfix.md`, «Шаги», п. 5 |
| `bugfix-scope-creep` | [ВНИМАНИЕ] | В диффе баг-фикса попутные правки вне причины и того же дефекта рядом: рефактор, переименования, стиль | `approaches/process/bugfix.md`, «Шаги», п. 5 |
| `bugfix-damaged-data-unaddressed` | [ВНИМАНИЕ] | Баг-фикс без разбора данных, которые баг успел записать (БД, кэш, события, внешние системы): нет раздела «Испорченные данные» в `bug.md` или пропущено место записи по причине бага | `approaches/process/bugfix.md`, «Шаги», п. 7 |
| `check-silenced` | [ВНИМАНИЕ] | Новая в диффе заглушка проверки без причины комментарием рядом: `//nolint` без линтера или без `// причина`, `@phpstan-ignore*`, пропуск теста (`t.Skip`, `markTestSkipped`/`markTestIncomplete`; причина — и в сообщении пропуска), исключение из покрытия (`#[CodeCoverageIgnore]`, `@codeCoverageIgnore`), ослабление конфига анализатора (понижение `level`, новые `ignoreErrors`/`excludePaths`, `exclusions`/`disable`) | `core/principles.md`, «Честность результата» |

## Стиль и текст

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `duplicated-format-logic` | [ОШИБКА] / [ВНИМАНИЕ] / [ИНФО] | Преобразование формата (телефон, ИИН, карта, деньги, даты) inline: есть в SDK/ядре или хелпере проекта — [ОШИБКА]; вторая копия без хелпера — [ВНИМАНИЕ]; одно вхождение, хелпера нет — [ИНФО] с предложением завести | `core/principles.md`, «Повторяющаяся обработка — один хелпер» |
| `nested-call-chain` | [ВНИМАНИЕ] | Вызов вложен в вызов глубже одного уровня, индекс/метод по результату вызова на месте, замыкание-поведение, длинная лямбда, затенение значимых имён, глубина блоков > 2 | `core/style.md`, «Форма кода» |
| `else-after-return` | [ИНФО] | `else` после ветки с выходом, вложенный `if` вместо раннего выхода, цепочка `if/else if` по значению | `core/style.md`, «Форма кода» |
| `too-many-params` | [ВНИМАНИЕ] | Больше 3 аргументов у функции или метода вместо `Params`/DTO; не в счёт — конструкторы, `SetRoutes` маршрутов Go, `ctx context.Context` и `t`/`b`/`f` тестов Go; допустимо до 3 позиционных + DTO опций последним и у приватного шага длинного метода до ~5 | `core/style.md`, «Форма кода» |
| `comment-restates-name` | [ВНИМАНИЕ] | Описание метода/класса/поля пересказывает имя | `core/style.md`, «Комментарии» |
| `ai-style-comment` | [ВНИМАНИЕ] | Комментарий-пересказ кода, объяснение устройства слоёв и связей, «ИИ-шная» простыня; у порта — кто и как его реализует, история вместо того, что он делает | `core/style.md`, «Комментарии» |
| `ai-style-text` | [ВНИМАНИЕ] | «ИИ-вода» в тексте для людей вне комментариев: Swagger, ошибки клиенту, коммит, MR, ответ в треде | `core/style.md`, «Любой текст для людей» |
| `task-artifact-ref-in-code` | [ВНИМАНИЕ] | Ссылка на артефакт задачи в коде или тестах (`design §4.2`, `QA-405`, `R15`, `T3`): `docs/tasks/` не в репозитории | `core/style.md`, «Комментарии» |
| `function-too-long` | [ИНФО] | Функция/метод длиннее ~50 строк — выделить шаги в приватные методы; не для метода репозитория (запрос не дробится) | `core/style.md`, «Форма кода» |
| `class-too-long` | [ВНИМАНИЕ] | Класс/файл длиннее ~500 строк без показанного деления на ответственности или обоснования в 1–2 строки | `core/style.md`, «Форма кода» |
| `duplicated-handler-skeleton` | [ОШИБКА] / [ВНИМАНИЕ] / [ИНФО] | Скелет инфраструктурного обработчика (consumer, джоба транспорта, адаптер) повторён: общий скелет в проекте или SDK уже есть — [ОШИБКА]; третья копия — [ВНИМАНИЕ] «вынести в общий компонент»; вторая — [ИНФО] «кандидат на вынос». Повтор в Service, Command/Query, Workflow — не находка (O-25) | `core/style.md`, «Форма кода»; `core/principles.md`, «Инфраструктурный код — один раз» |
| `infra-logic-duplicated` | [ОШИБКА] / [ВНИМАНИЕ] / [ИНФО] | Доменно-нейтральная проблема (транспорт, сериализация, ретраи, `x-death`, проверка `httpInfo`, обёртка клиента) решена своей реализацией: общий компонент проекта уже есть — [ОШИБКА]; третья копия — [ВНИМАНИЕ] «вынести в общий компонент»; вторая — [ИНФО] «кандидат на вынос». Есть в SDK/ядре — `sdk-reimplemented` (Go) / `repository-reinvents-core-helper` (PHP). Не находка — одинаковая цепочка middleware в `routes.go` модулей Go (`stacks/go/go-sdk/entrypoint/http.md`, «routes.go») | `core/principles.md`, «Инфраструктурный код — один раз» (O-27); Go — `conventions.md` п. 22 |
