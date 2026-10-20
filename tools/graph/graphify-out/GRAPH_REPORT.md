# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 106 files · ~86,484 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1138 nodes · 4325 edges · 11 communities detected
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 218 edges (avg confidence: 0.83)
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
1. `stacks/php/checklist.md` - 100 edges
2. `stacks/php/conventions.md` - 98 edges
3. `stacks/go/checklist.md` - 84 edges
4. `Транзакции` - 77 edges
5. `stacks/go/review-findings.md` - 75 edges
6. `Репозиторий` - 74 edges
7. `architecture/layers.md` - 71 edges
8. `stacks/php/review-findings.md` - 71 edges
9. `именование` - 70 edges
10. `approaches/process/role-chain.md` - 69 edges

## Surprising Connections (you probably didn't know these)
- `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` --conflicts_with--> `Ревью ⇄ исправления — до 3 кругов`  [AMBIGUOUS]
  corpus/agents/df-review.md → corpus/approaches/process/planning.md
- `Сценарии включают «одновременно» и «повтор»` --semantically_similar_to--> `Архитектор отмечает раздел «Конкурентность» в design.md; нет одновременности — одна строка`  [INFERRED] [semantically similar]
  corpus/approaches/process/analysis.md → corpus/approaches/patterns/concurrency.md
- `Смена статуса — условный UPDATE WHERE status=:expected или SELECT FOR UPDATE (race-on-status)` --semantically_similar_to--> `Перед переходом статуса проверка текущего; финальный статус не перезаписывается`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/concurrency.md → corpus/approaches/patterns/idempotency.md
- `Внутри транзакции с блокировкой — никаких внешних вызовов` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, публикации, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/concurrency.md → corpus/approaches/patterns/transactions.md
- `Нельзя отправлять наружу внутри транзакции` --semantically_similar_to--> `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep внутри (long-transaction)`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/outbox.md → corpus/approaches/patterns/performance.md

## Hyperedges (group relationships)
- **Workflow транзакционен по умолчанию (O-8)** — rule_transactions_workflow_tx_by_default, rule_transactions_go_only_repos_in_fn, rule_decisions_o8_workflow_tx, rule_checklist_workflow_one_txmanager_exec, rule_checklist_only_repos_in_fn, rule_conventions_atomic_writes [INFERRED 0.90]
- **Статус без потребителя — тупик** — rule_payments_status_has_consumer, rule_impact_analysis_status_without_consumer, rule_idempotency_final_status_not_overwritten [INFERRED 0.80]
- **O-19: сохранить контракт или по правилам, без переключателей** — rule_legacy_refactor_always_ask_keep_or_rules, rule_legacy_refactor_no_toggles, rule_stack_migration_contracts_by_user_answer, rule_stack_migration_no_toggles, rule_decisions_o19_contract_question, rule_rest_api_refactor_always_ask, rule_dev_architect_contracts_open_question, rule_dev_architect_feature_flag_only_new [INFERRED 0.90]
- **Лимит параллельных разработчиков 3 / refactor-migrate 5** — rule_role_chain_parallel_3_refactor_5, rule_dev_max_3_agents_one_tree, rule_dev_architect_concurrent_limit_3_5, rule_decisions_o20_task_size_limits [INFERRED 0.90]
- **Контракт репозитория (один запрос, nil,nil, виден целиком)** — rule_layers_repo_one_query, rule_checklist_repo_one_query, rule_layers_repo_contract_nil, rule_checklist_repo_nil_no_findorcreate, rule_layers_query_visible_whole, rule_checklist_query_visible [INFERRED 0.90]
- **Скоуп и права доступа (403/404, middleware)** — rule_layers_scope_two_cases, rule_layers_permission_middleware, rule_checklist_scope_404_403, rule_checklist_permission_middleware, rule_go_tariff_readme_permission_and_scope_middleware, rule_php_examples_readme_scope_param_required, rule_structure_write_routes_json_only [INFERRED 0.85]
- **Вопрос формы пагинации P-19 в нескольких файлах** — rule_decisions_p19_pagination_question, rule_df_php_list_form_question, rule_dev_php_list_form_from_design, rule_dev_architect_list_question_p19 [INFERRED 0.85]
- **Источник и версия Go SDK (O-4, п. 23) в нескольких файлах** — rule_decisions_o4_sdk_from_gomod, rule_go_conventions_sdk_source_version_23, rule_dev_go_no_code_before_sdk, rule_df_go_sdk_version_to_summary, rule_dev_architect_sdk_section_go [INFERRED 0.85]
- **Подтверждение коммита/пуша и запрет подписи ИИ — одно правило в 5 файлах** — rule_principles_commit_push_each_time, rule_git_commit_push_confirm, rule_self_check_commit_after_confirmation, rule_role_chain_db_and_commit_explicit_yes, rule_principles_no_ai_coauthor, rule_git_no_ai_trailers, rule_style_no_ai_in_code_git, rule_subagent_no_ai_signature [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Политика ACK/NACK consumer** — rule_rabbit_nil_ack_error_nack, rule_rabbit_deserialization_ack, rule_checklist_consumer_ack_nack, rule_go_tariff_readme_consumer_bad_payload_ack [INFERRED 0.85]
- **Побочные эффекты (HTTP, очередь) — вне транзакции, после коммита; outbox в той же транзакции** — rule_postgres_no_side_effects_in_tx, rule_tariff_readme_http_outside_tx, rule_tariff_readme_outbox_same_tx, rule_php_checklist_side_effects_after_tx, rule_php_conv_side_effects_after_commit, rule_php_example_limit_before_tx [INFERRED 0.85]
- **Workflow не вызывает workflow — одно правило в 3 файлах** — rule_go_workflow_no_workflow_calls_workflow, rule_php_checklist_cross_domain_workflow_only, rule_php_review_findings_workflow_calls_workflow [INFERRED 0.85]
- **Скоуп владельца: 403 в middleware, чужая по ID — 404, без middleware — ошибка** — rule_tariff_readme_scope_parkingids_404, rule_tariff_readme_parkingids_without_mw_error, rule_go_security_idor_each_handler, rule_php_checklist_tenant_scope, rule_php_conv_permissions_middleware, rule_php_example_requesthelper_throws, rule_php_example_scope_no_default [INFERRED 0.85]
- **service-direct-db-query: запросы к БД только в репозитории** — rule_go_review_checks_storage_queries_outside_infra_grep, rule_php_review_checks_db_queries_outside_repo_grep, rule_php_checklist_no_db_queries_outside_repo, rule_php_conventions_db_only_in_repository, rule_php_review_findings_no_db_in_workflow [INFERRED 0.85]
- **Запрет связей и expanded — 4 файла** — rule_mpscore_model_no_relations, rule_mpscore_repository_no_expanded, rule_mpscore_service_no_expanded, rule_review_findings_no_relations_no_expanded [INFERRED 0.85]
- **getOrCreate: ошибка или нет зависит от версии utils** — rule_php_review_checks_getorcreate_is_error, rule_php_conventions_getorcreate_deprecated_only_utils_264, rule_php_capabilities_getorset_utils_264 [INFERRED 0.70]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (157): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/legacy-refactor.md (+149 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (154): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, core/decisions.md, core/principles.md, core/rules-evolution.md, regulations/code-review.md, regulations/git.md (+146 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (150): regulations/rest-api.md, regulations/service-requirements.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md (+142 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (146): approaches/patterns/outbox.md, approaches/patterns/transactions.md, architecture/cross-domain.md, architecture/layers.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/postgres.md, stacks/go/go-sdk/workflow.md, stacks/php/mps-core/commands.md (+138 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (105): core/style.md, regulations/entity.md, stacks/go/conventions.md, stacks/go/style.md, stacks/php/checklist.md, stacks/php/mps-core/model.md, stacks/php/style.md, stacks/qa-playwright/conventions.md (+97 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (96): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/review-findings.md, stacks/go/security.md, stacks/php/security.md, Секреты (ключи, JWT-секреты, креды) в Redis не кладутся (+88 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (87): approaches/patterns/async.md, approaches/patterns/idempotency.md, architecture/integrations.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/entrypoint.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md (+79 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (73): skills/dev-php/SKILL.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/domain.md, stacks/php/mps-core/rules.md, stacks/php/README.md (+65 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (68): agents/df-qa.md, approaches/process/self-check.md, approaches/process/testing.md, skills/dev-go/SKILL.md, skills/dev-qa/SKILL.md, stacks/go/checklist.md, stacks/go/structure.md, stacks/php/mps-core/tests.md (+60 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.07
Nodes (63): approaches/patterns/cache.md, approaches/patterns/concurrency.md, approaches/patterns/performance.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/cli.md, Cache-aside inline в сервисе/workflow-потребителе, не в репозитории и не в отдельном классе; «резолверы» отклонены, Инвалидация — при записи в том же сценарии (InvalidateXCache); Go — Unlink, Ключ {service}:{entity}:{id}; префикс сервиса обязателен (общий Redis); все параметры значения в ключе (cache-key-incomplete) (+55 more)

### Community 10 - "Community 10"
Cohesion: 0.11
Nodes (39): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Миграции — NewMigrator(...).Up() из консольной команды; kernel миграции не катит, Любое изменение структуры БД — раздел «Схема БД» по technical-approval.md и пункт в «Открытые вопросы» на согласование, Миграция строго по «Схеме БД» design.md; другое поле/таблица — вопрос, Задача с миграцией — только при «да» на «Схему БД» в answers.md, Миграция уникального индекса: до индекса проверка дублей, нашлись — падает со списком; данные не правятся молча; FK с RESTRICT (+31 more)

## Ambiguous Edges - Review These
- `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` → `Ревью ⇄ исправления — до 3 кругов`  [AMBIGUOUS]
  corpus/agents/df-review.md · relation: conflicts_with
- `Каждый запрос — с таймаутом (Go context.WithTimeout, 10s по умолчанию; PHP — в конфиге)` → `Таймаут builder по умолчанию 30s; таймаут — SetRequestTimeout на каждый запрос, адрес и таймаут — из конструктора`  [AMBIGUOUS]
  corpus/approaches/patterns/performance.md · relation: conflicts_with
- `DI только в сборке; резолв из контейнера (di.Resolve, app(), resolve()) в бизнес-коде — [ОШИБКА]; Go — сервисы конкретным типом, PHP — ServiceInterface всегда` → `Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/migrations.md · relation: conflicts_with
- `п.39 Прогнаны go build/vet/test ./... и gofmt -l internal pkg (с горутинами -race); не запускал — так и написано` → `Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .` → `Работа с RabbitMQ только через gosdk-rabbit-core (пакет github.com/exgamer/gosdk-rabbit-core)`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/entrypoint/rabbit.md · relation: conflicts_with
- `Consumer: битый payload — CaptureError и ACK; временная — NACK в retry, после N попыток (x-death) — CaptureError и ACK; контекст consumer, не internal` → `Ошибка десериализации payload — постоянная: вернуть nil (ACK), не error`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/entrypoint/rabbit.md · relation: conflicts_with
- `Миграция уникального индекса: до индекса проверка дублей, нашлись — падает со списком; данные не правятся молча; FK с RESTRICT` → `Новая обязательная колонка в таблице с данными — три миграции: nullable → данные → снять nullable`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/migrations.md · relation: conflicts_with
- `Путь consumer обязан содержать internal/entrypoints/{context}/rabbit/; иначе [ОШИБКА] (в т.ч. entrypoint/consumer/ без rabbit/)` → `Каталог internal в контексте нельзя; consumer — контекст consumer, вебхуки — integration, служебные — system/http (G-4)`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/entrypoint/rabbit.md · relation: conflicts_with
- `Инфраструктура только через интерфейсы; сервисы доменов можно конкретным типом, интерфейс «на всякий случай» не заводится` → `RepositoryInterface и ServiceInterface — всегда; Command/Query — без интерфейса`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/workflow.md · relation: conflicts_with
- `Workflow с записью — в одном TransactionManagerInterface::run(); без транзакции — только с причиной в design.md` → `Workflow с записью — всегда в транзакции, включая одиночный create (O-8) — workflow-without-transaction`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Форма (1): CRUDRepository::search(..., int $perPage = 30, ...)` → `Форма (1): контроллер setPerPage(validated(per_page, 20))`  [AMBIGUOUS]
  corpus/stacks/php/conventions.md · relation: conflicts_with
- `utils ≥ 2.6.4 — getOrSet вместо getOrCreate (@deprecated); SmartCache — utils ≥ 2.8` → `Таблица 5.2: $cache->getOrCreate вместо SmartCache/getOrSet — [ОШИБКА] (поправка: по версии utils)`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `IndexRequest: per_page between:1,100, page gt:0` → `Форма (1): контроллер setPerPage(validated(per_page, 20))`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/repository.md · relation: conflicts_with
- `Workflow с записью — всегда в транзакции, включая одиночный create (O-8) — workflow-without-transaction` → `workflow-without-transaction: workflow пишет без run() и без причины в design.md (O-8)`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Enums/Models: описание класса и свойств (docblock) обязательны` → `@param/@return — только если добавляют информацию сверх сигнатуры`  [AMBIGUOUS]
  corpus/stacks/php/review-findings.md · relation: conflicts_with

## Knowledge Gaps
- **3 isolated node(s):** `версия SDK`, `Правила ревью по задетым слоям, иначе контекст 100k+ токенов`, `Связи создают неявные запросы и соблазн обойти Repository`
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` and `Ревью ⇄ исправления — до 3 кругов`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Каждый запрос — с таймаутом (Go context.WithTimeout, 10s по умолчанию; PHP — в конфиге)` and `Таймаут builder по умолчанию 30s; таймаут — SetRequestTimeout на каждый запрос, адрес и таймаут — из конструктора`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `DI только в сборке; резолв из контейнера (di.Resolve, app(), resolve()) в бизнес-коде — [ОШИБКА]; Go — сервисы конкретным типом, PHP — ServiceInterface всегда` and `Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `п.39 Прогнаны go build/vet/test ./... и gofmt -l internal pkg (с горутинами -race); не запускал — так и написано` and `Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .` and `Работа с RabbitMQ только через gosdk-rabbit-core (пакет github.com/exgamer/gosdk-rabbit-core)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Consumer: битый payload — CaptureError и ACK; временная — NACK в retry, после N попыток (x-death) — CaptureError и ACK; контекст consumer, не internal` and `Ошибка десериализации payload — постоянная: вернуть nil (ACK), не error`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Миграция уникального индекса: до индекса проверка дублей, нашлись — падает со списком; данные не правятся молча; FK с RESTRICT` and `Новая обязательная колонка в таблице с данными — три миграции: nullable → данные → снять nullable`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._