# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 108 files · ~103,633 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1037 nodes · 4169 edges · 12 communities detected
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 207 edges (avg confidence: 0.82)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Роли, агенты, процесс|Роли, агенты, процесс]]
- [[_COMMUNITY_Транзакции и конкурентность|Транзакции и конкурентность]]
- [[_COMMUNITY_Ревью и безопасность|Ревью и безопасность]]
- [[_COMMUNITY_Разработчик, тесты, Go-памятка|Разработчик, тесты, Go-памятка]]
- [[_COMMUNITY_Слои, домен, DTO|Слои, домен, DTO]]
- [[_COMMUNITY_Кэш, DI, модули SDK|Кэш, DI, модули SDK]]
- [[_COMMUNITY_Git, MR, эволюция правил|Git, MR, эволюция правил]]
- [[_COMMUNITY_REST, Swagger, PHP-памятка|REST, Swagger, PHP-памятка]]
- [[_COMMUNITY_Стиль кода и текста|Стиль кода и текста]]
- [[_COMMUNITY_Ошибки и HTTP вход|Ошибки и HTTP вход]]
- [[_COMMUNITY_Community 10|Community 10]]
- [[_COMMUNITY_Community 11|Community 11]]

## God Nodes (most connected - your core abstractions)
1. `stacks/php/checklist.md` - 102 edges
2. `stacks/php/conventions.md` - 97 edges
3. `stacks/php/structure.md` - 82 edges
4. `approaches/process/review.md` - 80 edges
5. `stacks/go/checklist.md` - 74 edges
6. `репозиторий` - 72 edges
7. `транзакции` - 72 edges
8. `architecture/layers.md` - 72 edges
9. `approaches/process/review-tags.md` - 71 edges
10. `stacks/go/conventions.md` - 61 edges

## Surprising Connections (you probably didn't know these)
- `Нельзя: отправлять наружу внутри транзакции; своё соединение с брокером в обход ядра (amqp.Dial, MR!151)` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/outbox.md → corpus/approaches/patterns/transactions.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `Без переключателей: ни флагов, ни shadow/dual-write, ни процента трафика; маршрут переводится одним деплоем` --semantically_similar_to--> `Переключателей нет: ни feature-флага, ни DI по настройке, ни процента трафика, ни shadow/dual-write (refactor-toggle)`  [INFERRED] [semantically similar]
  corpus/approaches/process/stack-migration.md → corpus/approaches/process/legacy-refactor.md
- `Никогда не git commit / push без явного подтверждения на каждый раз` --semantically_similar_to--> `Характеризующие тесты фиксируются до правок: отдельным коммитом только по «да» пользователя, иначе хэшем в plan.md`  [INFERRED] [semantically similar]
  corpus/core/principles.md → corpus/approaches/process/legacy-refactor.md
- `Унаследованное нарушение (не в diff) — [ВНИМАНИЕ] «унаследовано» + строка в rules-log, не [ОШИБКА]; безопасность — всегда по каталогу; без diff п. 3a не применяется` --semantically_similar_to--> `Infrastructure всегда с типом (P-10): Infrastructure/{Postgres,Redis,Http,RabbitMQ}/…; без типа в новом коде — file-wrong-location, существующий — унаследованное (review.md п. 3a)`  [INFERRED] [semantically similar]
  corpus/approaches/process/review.md → corpus/stacks/php/structure.md

## Hyperedges (group relationships)
- **Правила платёжного колбэка (идемпотентность, подпись, кэш ошибок, outbox) в payments.md и Go review-findings** — rule_patterns_payments_idempotency_unique_txn, rule_patterns_payments_signature_before_state, rule_patterns_payments_no_cache_errors, rule_go_review_findings_pay_idempotency_unique, rule_go_review_findings_signature_each_gateway, rule_go_review_findings_no_cache_gateway_errors [INFERRED 0.90]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Правило транзакций O-8 в нескольких файлах** — rule_decisions_o8_transaction, rule_go_conventions_tx_atomicity, rule_go_checklist_tx_more_than_one_write, rule_go_sdk_workflow_tx_only_args, rule_review_tags_needless_tx_warning [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **SetTimeout/SetPerPage пагинатора описаны в 4 файлах** — rule_capabilities_paginator_timeout_seconds_perpage, rule_postgres_paginator_settimeout_seconds, rule_tariff_readme_paginator_settimeout_seconds, rule_review_tags_paginator_timeout_units_error [INFERRED 0.90]
- **Транзакции PHP через TransactionManagerInterface** — rule_review_checks_tx_via_manager_interface, rule_review_checks_single_crud_no_tx, rule_structure_tx_manager_core_version [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (174): regulations/entity.md, regulations/rest-api.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/exceptions.md, stacks/php/conventions.md, stacks/php/mps-core/capabilities.md (+166 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (163): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/self-check.md, core/backlog.md, core/decisions.md, core/principles.md (+155 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (128): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/postgres.md (+120 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.05
Nodes (111): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/planning.md (+103 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.06
Nodes (67): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/testing.md, regulations/contracts.md, skills/dev-go/SKILL.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах (+59 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.06
Nodes (67): architecture/cross-domain.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/go/structure.md, stacks/php/mps-core/cli.md, stacks/php/mps-core/commands.md, stacks/php/mps-core/queries.md, Базовый класс ядра передаёт параметры через состояние (setQueryParams → get → prepare): сигнатура не видна и не типизируется (+59 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.06
Nodes (66): approaches/patterns/cache.md, regulations/service-requirements.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/infra/redis.md, stacks/go/go-sdk/main.md, stacks/go/review-findings.md, stacks/php/mps-core/exceptions.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены (+58 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (61): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Почему группы интерфейсов: файлы по одному интерфейсу дробили модуль (screen-gateway: 34 файла в 7 модулях), UI-тесты: Python + Playwright + pytest (POM), Описание коммита по-русски, с маленькой буквы, без точки, до 70 символов, 4+ аргумента у функции (не конструктора; ctx не в счёт) → Params в dto.go (+53 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.07
Nodes (59): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/php/security.md, Код с записью/запросами к БД — transactions/concurrency/performance.md всегда; платёжный путь — payments.md, [БЕЗОПАСНОСТЬ] — всегда КРИТИЧНО; каждая мера threats.md реализована, Аудит: 4–8 параллельных read-only агентов Explore (+51 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.07
Nodes (59): architecture/layers.md, stacks/go/checklist.md, stacks/go/conventions.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/module.md, stacks/go/security.md, Почему один запрос: порядок шагов и граница транзакции — решение, репозиторий с двумя запросами прячет его, Без design.md — только мелкая правка: 1 модуль, без новых эндпойнтов, миграций, событий, контракта, чувствительных зон (+51 more)

### Community 10 - "Community 10"
Cohesion: 0.07
Nodes (51): architecture/integrations.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, Общее для нескольких сервисов, чего нет в SDK — вопрос «добавить в SDK?» пользователю, не универсальный пакет молча, Функции нет в справочнике нужной версии — её нет, не выдумывать; справочник пересобрать tools/sdk-ref/go.sh, Транзакция на 1–5 репозиториев — NewManager…NewManager5; TxManager объявляет потребитель, Есть в SDK — использовать SDK; своя реализация — тег sdk-reimplemented (+43 more)

### Community 11 - "Community 11"
Cohesion: 0.14
Nodes (31): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill, Миграция данных — отдельной миграцией, обратимой где возможно, Удаление читаемой колонки/таблицы — в два релиза: сначала код перестаёт читать, потом миграция, FK объявляется явно с поведением UPDATE/DELETE (по умолчанию RESTRICT); индексы под фильтры/сортировки/уникальность — в той же миграции (+23 more)

## Ambiguous Edges - Review These
- `Круг N: памятка, security.md и каталоги тегов — всегда; прочие правила — по слоям изменённых файлов` → `В цепочке /dev ревью — отдельный субагент без контекста разработки, только чтение; ревьюятся код и тесты`  [AMBIGUOUS]
  corpus/agents/df-review.md · relation: conflicts_with
- `Ручные ack/nack, где результат обработчика их уже выражает, не пишутся` → `Consumer тонкий и идемпотентный; ошибка десериализации — ACK, временная — NACK; топология — при старте через SDK`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` → `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`  [AMBIGUOUS]
  corpus/core/principles.md · relation: conflicts_with
- `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` → `Преобразование формата — один раз: SDK → pkg/; вторая копия — вынести (В); inline при готовом — О`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log` → `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)` → `0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `missing-auth-middleware — [БЕЗОПАСНОСТЬ]; межсервисный роут без auth исторически — вопрос «осознанно?» в MR` → `Новый роут без auth-middleware — только осознанно, с причиной в MR (О)`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` → `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `Common → ни от чего, кроме языка и библиотек дат` → `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип` → `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`  [AMBIGUOUS]
  corpus/regulations/contracts.md · relation: conflicts_with
- `O-22 (открыт): граф кода проекта не введён; в варианте б/в граф не коммитится, обновляет координатор` → `Граф и кэш правил коммитятся вместе с правкой`  [AMBIGUOUS]
  corpus/skills/dev-rule/SKILL.md · relation: conflicts_with
- `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)` → `Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true` → `В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `Модель: @property по-русски, $table со схемой, $fillable, $casts [О]` → `Модели: @property на каждое поле, описание по-русски — у неочевидных; $table со схемой, public — без префикса`  [AMBIGUOUS]
  corpus/stacks/php/checklist.md · relation: conflicts_with
- `Версия ядра — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor/mps проекта, а не с примерами mps-core/*.md` → `При расхождении с этим файлом верна официальная дока нужной версии; метода нет в справочнике версии проекта — его нет`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/capabilities.md · relation: conflicts_with
- `IndexRequest: page min:0 max Page::MAX_PAGE, per_page sometimes between:1,MAX_PER_PAGE, id max ID_MAX` → `per_page задаётся явно (в IndexRequest/SearchRequest и в аннотации), не «по умолчанию где-то в ядре»`  [AMBIGUOUS]
  corpus/stacks/php/review-findings.md · relation: conflicts_with
- `Репозиторий без бизнес-логики и бизнес-исключений, в т.ч. NotFoundAppException — возвращает null, «не найдено» решает вызывающий` → `Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории`  [AMBIGUOUS]
  corpus/stacks/php/structure.md · relation: conflicts_with

## Knowledge Gaps
- **5 isolated node(s):** `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, контекст оркестратора, расход квоты; последовательные волны ограничивают выигрыш`, `Почему один запрос: порядок шагов и граница транзакции — решение, репозиторий с двумя запросами прячет его`, `Почему группы интерфейсов: файлы по одному интерфейсу дробили модуль (screen-gateway: 34 файла в 7 модулях)`, `Базовый класс ядра передаёт параметры через состояние (setQueryParams → get → prepare): сигнатура не видна и не типизируется`
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Круг N: памятка, security.md и каталоги тегов — всегда; прочие правила — по слоям изменённых файлов` and `В цепочке /dev ревью — отдельный субагент без контекста разработки, только чтение; ревьюятся код и тесты`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Ручные ack/nack, где результат обработчика их уже выражает, не пишутся` and `Consumer тонкий и идемпотентный; ошибка десериализации — ACK, временная — NACK; топология — при старте через SDK`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` and `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` and `Преобразование формата — один раз: SDK → pkg/; вторая копия — вынести (В); inline при готовом — О`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log` and `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)` and `0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._