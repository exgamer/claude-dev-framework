# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 108 files · ~104,315 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1058 nodes · 4289 edges · 11 communities detected
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 211 edges (avg confidence: 0.81)
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

## God Nodes (most connected - your core abstractions)
1. `stacks/php/checklist.md` - 102 edges
2. `stacks/go/checklist.md` - 101 edges
3. `stacks/php/conventions.md` - 96 edges
4. `stacks/php/structure.md` - 82 edges
5. `approaches/process/review.md` - 80 edges
6. `stacks/php/examples/README.md` - 78 edges
7. `транзакции` - 76 edges
8. `репозиторий` - 75 edges
9. `architecture/layers.md` - 72 edges
10. `approaches/process/review-tags.md` - 71 edges

## Surprising Connections (you probably didn't know these)
- `Инвалидация — при записи, в том же сценарии, что меняет данные` --conflicts_with--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/transactions.md
- `Go-код с горутинами/общим состоянием — go test -race ./...` --semantically_similar_to--> `Go с горутинами — go test -race; уязвимости — govulncheck / composer audit; Go: build, vet, test, gofmt -l`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/concurrency.md → corpus/approaches/process/self-check.md
- `Без переключателей: ни флагов, ни shadow/dual-write, ни процента трафика; маршрут переводится одним деплоем` --semantically_similar_to--> `Переключателей нет: ни feature-флага, ни DI по настройке, ни процента трафика, ни shadow/dual-write (refactor-toggle)`  [INFERRED] [semantically similar]
  corpus/approaches/process/stack-migration.md → corpus/approaches/process/legacy-refactor.md
- `Никогда не git commit / push без явного подтверждения на каждый раз` --semantically_similar_to--> `Характеризующие тесты фиксируются до правок: отдельным коммитом только по «да» пользователя, иначе хэшем в plan.md`  [INFERRED] [semantically similar]
  corpus/core/principles.md → corpus/approaches/process/legacy-refactor.md
- `Массовые операции, очистка, миграции — не без явного «да» на каждую` --semantically_similar_to--> `Действия с БД и коммиты/пуш — только по явному «да» на каждое`  [INFERRED] [semantically similar]
  corpus/approaches/process/self-check.md → corpus/approaches/process/role-chain.md

## Hyperedges (group relationships)
- **Правила платёжного колбэка (идемпотентность, подпись, кэш ошибок, outbox) в payments.md и Go review-findings** — rule_patterns_payments_idempotency_unique_txn, rule_patterns_payments_signature_before_state, rule_patterns_payments_no_cache_errors, rule_go_review_findings_pay_idempotency_unique, rule_go_review_findings_signature_each_gateway, rule_go_review_findings_no_cache_gateway_errors [INFERRED 0.90]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Правило транзакций O-8 в нескольких файлах** — rule_decisions_o8_transaction, rule_go_conventions_tx_atomicity, rule_go_checklist_tx_more_than_one_write, rule_go_sdk_workflow_tx_only_args, rule_review_tags_needless_tx_warning [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **SetTimeout/SetPerPage пагинатора описаны в 4 файлах** — rule_capabilities_paginator_timeout_seconds_perpage, rule_postgres_paginator_settimeout_seconds, rule_tariff_readme_paginator_settimeout_seconds, rule_review_tags_paginator_timeout_units_error [INFERRED 0.90]
- **Пара эталонов тарифа Go/PHP: одинаковый сценарий (outbox, лимит цены до транзакции, принятый риск гонки)** — file_stacks_go_examples_tariff_readme_md, file_stacks_php_examples_readme_md, rule_go_tariff_readme_create_tx_two_writes, rule_php_examples_readme_create_tx_two_writes, rule_go_tariff_readme_accept_race_risk, rule_php_examples_readme_accept_race_risk [INFERRED 0.90]
- **Транзакции PHP через TransactionManagerInterface** — rule_review_checks_tx_via_manager_interface, rule_review_checks_single_crud_no_tx, rule_structure_tx_manager_core_version [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (160): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, core/backlog.md, core/principles.md, core/rules-evolution.md, regulations/code-review.md (+152 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (147): approaches/patterns/async.md, approaches/patterns/cache.md, approaches/patterns/idempotency.md, regulations/service-requirements.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/entrypoint/rabbit.md (+139 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (127): approaches/patterns/concurrency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md, stacks/php/examples/README.md, stacks/php/mps-core/cli.md (+119 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (123): architecture/cross-domain.md, architecture/integrations.md, regulations/entity.md, stacks/go/checklist.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/go-sdk/workflow.md (+115 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (116): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/planning.md (+108 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (109): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/exceptions.md, stacks/php/conventions.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md (+101 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (85): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, architecture/layers.md, skills/dev-review/SKILL.md, skills/dev-security/SKILL.md, stacks/go/security.md, stacks/php/security.md (+77 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (82): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/self-check.md, approaches/process/testing.md, core/decisions.md, regulations/contracts.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md (+74 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.07
Nodes (56): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, UI-тесты: Python + Playwright + pytest (POM), Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение, Имя обязано отражать поведение: Enqueue/dispatch без очереди — service-method-name-mismatch, Много аргументов функции (4+, не конструктор) → структура Params/Input (+48 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.14
Nodes (31): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill, Миграция данных — отдельной миграцией, обратимой где возможно, Удаление читаемой колонки/таблицы — в два релиза: сначала код перестаёт читать, потом миграция, FK объявляется явно с поведением UPDATE/DELETE (по умолчанию RESTRICT); индексы под фильтры/сортировки/уникальность — в той же миграции (+23 more)

### Community 10 - "Community 10"
Cohesion: 0.16
Nodes (22): stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/domain.md, DI (Register/Resolve, MustResolve только на старте) — только в app/bootstrap, a.Container — только внутри App (bootstrap, модули), никогда в бизнес-логику и репозитории, Зависимости kernel — через хелперы (GetDefaultPostgresConnection, GetRedisClient…), не di.Resolve, nil не передаётся как обязательная зависимость в конструктор (nil-dependency-injected), Модуль берёт у другого только объект через DI или Load{Name}(); владелец раньше в RegisterAndInitModules, взаимных зависимостей нет (+14 more)

## Ambiguous Edges - Review These
- `Круг N: памятка, security.md и каталоги тегов — всегда; прочие правила — по слоям изменённых файлов` → `В цепочке /dev ревью — отдельный субагент без контекста разработки, только чтение; ревьюятся код и тесты`  [AMBIGUOUS]
  corpus/agents/df-review.md · relation: conflicts_with
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` → `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`  [AMBIGUOUS]
  corpus/core/principles.md · relation: conflicts_with
- `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log` → `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)` → `0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` → `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `Common → ни от чего, кроме языка и библиотек дат` → `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип` → `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`  [AMBIGUOUS]
  corpus/regulations/contracts.md · relation: conflicts_with
- `B-57: значения enum в теге oneof=KZT RUB USD и lte=1440 дублируют enum/константы (enum-list-drift) — своё правило через CustomValidationRules()` → `п. 4: id > 0 (невалидный id пути — 422), page lte=10000, per_page lte=100, правила в теге binding (validate SDK не читает); права — RequirePermissionMiddleware на маршруте`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)` → `Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Эталон Go проверяется: go build && go vet && go test ./... && gofmt -l . && golangci-lint run ./...; PHP — tools/php-check/run.sh; не запускал — так и написано` → `Проверка эталона: go build && go vet && go test -race ./... && gofmt -l .; линтер — golangci-lint run ./... (v2)`  [AMBIGUOUS]
  corpus/skills/dev-rule/SKILL.md · relation: conflicts_with
- `п. 28: каждый хендлер скоупит по владельцу отдельно get/update/delete; скоуп — тип с явным All (ParkingScope); чужая по ID — 404, id владельца из запроса — 403` → `updateFromDto(int $id, TariffDto) — один UPDATE … WHERE через whereKey()->update(); скоуп — в findById/getByIdInParkings`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
- `Healthcheck: /healthcheck/* вне схемы /api/<context>/<version>; сбой — 503 без Sentry, причина — logger.Warning` → `Healthcheck — готовый MPS\Utils HealthCheck (/health/liveness, /health/readiness), своего нет`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
- `tariff.created: все поля обязательны, null нет (grace_minutes uint); подтверждение брокера; at-least-once, дедупликация по event_id` → `tariff.created: null только у grace_minutes; подтверждения брокера ядро не ждёт; at-least-once, дедупликация по event_id; расхождение с Go — особенность примера`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
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
- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` and `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log` and `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)` and `0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` and `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Common → ни от чего, кроме языка и библиотек дат` and `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._