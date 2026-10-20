# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 108 files · ~104,440 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1082 nodes · 4436 edges · 11 communities detected
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 212 edges (avg confidence: 0.82)
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
1. `stacks/php/checklist.md` - 103 edges
2. `stacks/go/checklist.md` - 102 edges
3. `stacks/php/conventions.md` - 97 edges
4. `approaches/process/review.md` - 84 edges
5. `stacks/php/structure.md` - 84 edges
6. `stacks/php/examples/README.md` - 79 edges
7. `репозиторий` - 77 edges
8. `транзакции` - 76 edges
9. `skills/dev-php/SKILL.md` - 76 edges
10. `architecture/layers.md` - 72 edges

## Surprising Connections (you probably didn't know these)
- `Инвалидация — при записи, в том же сценарии, что меняет данные` --conflicts_with--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/transactions.md
- `Две остановки: после документа и после плана; коммит и пуш — по отдельному «да» на каждый` --semantically_similar_to--> `Действия с БД и коммиты/пуш — только по явному «да» на каждое`  [INFERRED] [semantically similar]
  corpus/approaches/process/mr-threads.md → corpus/approaches/process/role-chain.md
- `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове` --conflicts_with--> `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]`  [AMBIGUOUS]
  corpus/core/principles.md → corpus/approaches/process/review-tags.md
- `Workflow берёт репозитории и сервисы доменов напрямую через конструктор; не вызывает другие workflow` --semantically_similar_to--> `Workflow: инфраструктура — только через интерфейсы; сервисы и Command доменов — можно конкретным типом`  [INFERRED] [semantically similar]
  corpus/architecture/cross-domain.md → corpus/stacks/go/go-sdk/workflow.md
- `Публичный метод репозитория — ровно один запрос; «выбрать → записать» — бизнес-логика в сервисе/Command/workflow (repository-multi-query, P-20)` --semantically_similar_to--> `п. 49: метод репозитория — один запрос; ручной client.Transaction в репозитории нет; порядок и txManager.Exec — в сервисе/Command/workflow`  [INFERRED] [semantically similar]
  corpus/architecture/layers.md → corpus/stacks/go/checklist.md

## Hyperedges (group relationships)
- **Правила платёжного колбэка (идемпотентность, подпись, кэш ошибок, outbox) в payments.md и Go review-findings** — rule_patterns_payments_idempotency_unique_txn, rule_patterns_payments_signature_before_state, rule_patterns_payments_no_cache_errors, rule_go_review_findings_pay_idempotency_unique, rule_go_review_findings_signature_each_gateway, rule_go_review_findings_no_cache_gateway_errors [INFERRED 0.90]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Правило транзакций O-8 в нескольких файлах** — rule_decisions_o8_transaction, rule_go_conventions_tx_atomicity, rule_go_checklist_tx_more_than_one_write, rule_go_sdk_workflow_tx_only_args, rule_review_tags_needless_tx_warning [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **SetTimeout/SetPerPage пагинатора описаны в 4 файлах** — rule_capabilities_paginator_timeout_seconds_perpage, rule_postgres_paginator_settimeout_seconds, rule_tariff_readme_paginator_settimeout_seconds, rule_review_tags_paginator_timeout_units_error [INFERRED 0.90]
- **Пара эталонов тарифа Go/PHP: одинаковый сценарий (outbox, лимит цены до транзакции, принятый риск гонки)** — file_stacks_go_examples_tariff_readme_md, file_stacks_php_examples_readme_md, rule_go_tariff_readme_create_tx_two_writes, rule_php_examples_readme_create_tx_two_writes, rule_go_tariff_readme_accept_race_risk, rule_php_examples_readme_accept_race_risk [INFERRED 0.90]
- **Транзакции PHP через TransactionManagerInterface** — rule_review_checks_tx_via_manager_interface, rule_review_checks_single_crud_no_tx, rule_structure_tx_manager_core_version [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (195): agents/df-analyst.md, agents/df-architect.md, agents/df-php.md, agents/df-qa.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/legacy-refactor.md (+187 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (173): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/self-check.md, core/backlog.md, core/rules-evolution.md, regulations/code-review.md (+165 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (146): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/checklist.md, stacks/go/examples/tariff/README.md (+138 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.05
Nodes (104): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/php/conventions.md, stacks/php/examples/README.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md (+96 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (99): agents/df-go.md, architecture/integrations.md, skills/dev-go/SKILL.md, stacks/go/conventions.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md (+91 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (92): architecture/layers.md, regulations/entity.md, stacks/go/go-sdk/domain.md, stacks/go/security.md, stacks/php/mps-core/model.md, stacks/php/mps-core/repository.md, stacks/php/mps-core/service.md, Почему один запрос: порядок шагов и граница транзакции — решение, репозиторий с двумя запросами прячет его (+84 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (83): approaches/patterns/cache.md, regulations/service-requirements.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/infra/redis.md, stacks/go/go-sdk/main.md, stacks/go/review-findings.md, stacks/php/mps-core/exceptions.md (+75 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (63): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Почему группы интерфейсов: файлы по одному интерфейсу дробили модуль (screen-gateway: 34 файла в 7 модулях), Коммит и пуш — только после явного «да»; без Co-Authored-By/Claude-Session, UI-тесты: Python + Playwright + pytest (POM), Без трейлеров ИИ: Co-Authored-By: Claude, Claude-Session, «Generated with Claude Code» — ни в коммите, ни в MR (+55 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.08
Nodes (55): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/php/security.md, Код с записью/запросами к БД — transactions, concurrency, performance всегда; платёжный путь — payments.md всегда, [БЕЗОПАСНОСТЬ] — всегда КРИТИЧНО; нереализованная мера threats.md — [БЕЗОПАСНОСТЬ], Аудит: 4–8 параллельных read-only агентов Explore (+47 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.1
Nodes (40): architecture/cross-domain.md, stacks/php/mps-core/cli.md, stacks/php/mps-core/commands.md, stacks/php/mps-core/queries.md, Базовый класс ядра передаёт параметры через состояние (setQueryParams → get → prepare): сигнатура не видна и не типизируется, Домен не зависит от чужого ни через репозиторий, ни через сервис, ни через «свой» интерфейс — domain-cross-domain-access (O-7), Циклов между доменами нет; общая часть A↔B — в workflow, Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица (+32 more)

### Community 10 - "Community 10"
Cohesion: 0.14
Nodes (32): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Миграция — строго по «Схеме БД» design.md; другое поле — вопрос, Миграция — строго по «Схеме БД» design.md; другое поле — вопрос, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill, Миграция данных — отдельной миграцией, обратимой где возможно (+24 more)

## Ambiguous Edges - Review These
- `Ревьюер-субагент код не правит; пишет только docs/tasks/<ключ>/review.md (и через Bash — нет)` → `Отчёт — файл review_result/{YYYY-MM-DD_HH-MM}_{type}.md в корне проекта («Один формат»)`  [AMBIGUOUS]
  corpus/approaches/process/review.md · relation: conflicts_with
- `Ревьюер-субагент исправления не предлагает — их назначает оркестратор` → `Итог: путь, вердикт, предложение исправить [ОШИБКИ]; коммит/пуш — по явному подтверждению`  [AMBIGUOUS]
  corpus/skills/dev-review/SKILL.md · relation: conflicts_with
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` → `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`  [AMBIGUOUS]
  corpus/core/principles.md · relation: conflicts_with
- `Унаследованное нарушение (нет в diff) — [ВНИМАНИЕ] «унаследовано» + строка в rules-log, не [ОШИБКА] и не повод для круга; безопасность — по каталогу всегда` → `Уровень — только из каталога: новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/skills/dev-review/SKILL.md · relation: conflicts_with
- `База ревью MR — Target Branch этого MR; не названа — stage (regulations/git.md)` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка в rules-log` → `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `lint.py фреймворка: каждая находка в своём коде исправлена до сдачи; база разработчика — merge-base с release, ревьюера — stage; чужое/старое — «унаследовано»; анализатор не заменяет памятку` → `D-1: ревьюер при прогоне анализатора без находок проверяет глазами только «Остаток — агенту» из RULES.md`  [AMBIGUOUS]
  corpus/approaches/process/self-check.md · relation: conflicts_with
- `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` → `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `Common → ни от чего, кроме языка и библиотек дат` → `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип` → `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`  [AMBIGUOUS]
  corpus/regulations/contracts.md · relation: conflicts_with
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

- **What is the exact relationship between `Ревьюер-субагент код не правит; пишет только docs/tasks/<ключ>/review.md (и через Bash — нет)` and `Отчёт — файл review_result/{YYYY-MM-DD_HH-MM}_{type}.md в корне проекта («Один формат»)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Ревьюер-субагент исправления не предлагает — их назначает оркестратор` and `Итог: путь, вердикт, предложение исправить [ОШИБКИ]; коммит/пуш — по явному подтверждению`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` and `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Унаследованное нарушение (нет в diff) — [ВНИМАНИЕ] «унаследовано» + строка в rules-log, не [ОШИБКА] и не повод для круга; безопасность — по каталогу всегда` and `Уровень — только из каталога: новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База ревью MR — Target Branch этого MR; не названа — stage (regulations/git.md)` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка в rules-log` and `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._