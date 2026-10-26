# Graph Report - dev-framework rules  (2026-10-26)

## Corpus Check
- 111 files · ~123,918 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1330 nodes · 5291 edges · 8 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 151 edges (avg confidence: 0.82)
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

## God Nodes (most connected - your core abstractions)
1. `approaches/process/review-tags.md` - 114 edges
2. `core/backlog.md` - 110 edges
3. `stacks/php/conventions.md` - 98 edges
4. `stacks/php/checklist.md` - 95 edges
5. `stacks/go/checklist.md` - 88 edges
6. `approaches/process/review.md` - 85 edges
7. `skills/dev-php/SKILL.md` - 85 edges
8. `транзакции` - 83 edges
9. `stacks/go/conventions.md` - 82 edges
10. `репозиторий` - 79 edges

## Surprising Connections (you probably didn't know these)
- `too-many-params [ВНИМАНИЕ]: больше 3 аргументов; не в счёт — конструкторы, SetRoutes Go, ctx; приватный шаг до ~5` --semantically_similar_to--> `SetRoutes(a, router, handler, auth…): роутер и auth собирает module.go; контейнера в routes.go нет; 4+ параметров — не too-many-params`  [INFERRED] [semantically similar]
  corpus/approaches/process/review-tags.md → corpus/stacks/go/go-sdk/entrypoint/http.md
- `Пустой скоуп — явное WHERE FALSE, а не IN ? с пустым списком` --semantically_similar_to--> `Скоуп — {Owner}Scope{All bool; IDs []uint}: пустой IDs без All — ничего; нужен 2+ доменам — в internal/common/ (scope-param-defaults-unrestricted)`  [INFERRED] [semantically similar]
  corpus/stacks/go/go-sdk/infra/postgres.md → corpus/stacks/go/conventions.md
- `Эталон: gosdk-core v1.0.10, db-core v1.0.10, http-core v1.1.0, request-builder v1.0.2, postgres-core v1.0.8, redis-core v1.0.7, rabbit-core v1.0.10, sentry-core v1.0.5, console-core v1.0.6 (Go 1.27.3+)` --semantically_similar_to--> `Справочник GitLab — версии эталона: core v1.0.10, db-core v1.0.10, http-core v1.1.0, postgres-core v1.0.8, redis-core v1.0.7, rabbit-core v1.0.10, request-builder v1.0.2, sentry-core v1.0.5, console-core v1.0.6; websocket v1.0.4`  [INFERRED] [semantically similar]
  corpus/stacks/go/examples/tariff/README.md → corpus/stacks/go/go-sdk/capabilities.md
- `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно` --conflicts_with--> `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md → corpus/stacks/php/mps-core/service.md
- `Агенту выданы только 4 IDE-инструмента чтения: search_symbol, get_symbol_info, search_structural, lint_files; model: inherit` --semantically_similar_to--> `Ни у одного агента нет Agent; модель у всех inherit, отдельной роли можно поставить model:`  [INFERRED] [semantically similar]
  corpus/agents/df-analyst.md → corpus/approaches/process/role-chain.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **До 3 параллельных разработчиков (до 5 в refactor/migrate)** — rule_planning_max_3_devs, rule_dev_max_3_agents, rule_planning_fresh_agent_per_task, rule_dev_fresh_agent_5min [INFERRED 0.90]
- **Уровень находки только в каталоге (O-24)** — rule_review_tags_level_only_here, rule_review_level_only_catalog, rule_review_tags_catalog_over_checklist, rule_review_tags_rule_without_tag [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Коммит/пуш только по «да» на каждый** — rule_git_no_commit_without_confirm, rule_dev_commit_push_only_yes, rule_dev_rule_commit_only_confirm, rule_checklist_commit_push_yes, rule_planning_same_worktree [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Лимит параллельных агентов 3/5** — rule_dev_architect_parallel_limit_3_5, rule_dev_parallel_agents_3_5, rule_backlog_b85_integration_runs_serial [INFERRED 0.80]
- **TransactionManagerInterface вместо repository->transaction** — rule_service_no_repo_transaction, rule_transactions_inject_tm_run, rule_review_checks_no_repo_transaction, rule_review_findings_tx_via_manager [INFERRED 0.90]
- **Запись только через DTO (п. 11a)** — rule_repository_write_dto_not_array, rule_review_findings_write_array_instead_of_dto, rule_service_crudservice_read_only, rule_security_mass_assignment_guarded_false [INFERRED 0.85]
- **Доступ — middleware и скоуп, не Request/контроллер** — rule_http_access_in_middleware, rule_review_findings_access_in_middleware, rule_repository_scope_key_mandatory, rule_security_tenant_scope_mandatory [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.02
Nodes (228): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, core/decisions.md, core/rules-evolution.md, regulations/code-review.md, regulations/git.md (+220 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.02
Nodes (223): regulations/rest-api.md, regulations/service-requirements.md, stacks/go/checklist.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md (+215 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (200): approaches/patterns/async.md, approaches/patterns/cache.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/payments.md, approaches/patterns/performance.md, approaches/patterns/transactions.md (+192 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (193): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/legacy-refactor.md (+185 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.02
Nodes (192): architecture/cross-domain.md, core/backlog.md, regulations/entity.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/module.md, stacks/go/go-sdk/workflow.md (+184 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.03
Nodes (147): agents/df-qa.md, approaches/patterns/migrations.md, approaches/process/bugfix.md, approaches/process/self-check.md, approaches/process/testing.md, core/principles.md, regulations/technical-approval.md, skills/dev-go/SKILL.md (+139 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (79): architecture/integrations.md, core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/infra/http.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md (+71 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (68): agents/df-security.md, approaches/process/security.md, architecture/layers.md, skills/dev-security/SKILL.md, stacks/go/security.md, Новый роут без auth: review.md — mr-hygiene [ВНИМАНИЕ], каталог — missing-auth-middleware [БЕЗОПАСНОСТЬ]; свести к тегу каталога, Неподтверждённая находка безопасности: в стеках «не флагай; при сомнении [ВНИМАНИЕ]», в каталоге — «[ВНИМАНИЕ] с пояснением риска»; свести к каталогу, Роль/право — middleware на маршруте (permission:/role:), не в контроллере; Auth:: в домене нет (+60 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `url-not-by-scheme [ВНИМАНИЕ]: путь нового эндпойнта по схеме /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; исключения для служебных путей нет` → `{context} не может быть internal; consumer событий — consumer, вебхуки — integration; служебные — system/http/healthcheck (G-4)`  [AMBIGUOUS]
  corpus/approaches/process/review-tags.md · relation: conflicts_with
- `id связанной записи вне скоупа или несуществующий — 422 по полю, один текст; id владельца скоупа (parking_id) — 403 в middleware` → `Хендлер — валидация (тег binding, validate SDK не читает), маппинг, вызов; права — middleware; исключение: create сверяет parking_id из тела со скоупом — 403`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]` → `Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано` → `Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/jobs.md · relation: conflicts_with
- `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)` → `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md · relation: conflicts_with

## Knowledge Gaps
- **10 isolated node(s):** `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Флаг — две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения`, `Знание о статусах в одном месте: сервис, сравнивающий статусы сам, расходится с соседним при первой правке`, `метка продолжения` (+5 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `url-not-by-scheme [ВНИМАНИЕ]: путь нового эндпойнта по схеме /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; исключения для служебных путей нет` and `{context} не может быть internal; consumer событий — consumer, вебхуки — integration; служебные — system/http/healthcheck (G-4)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `id связанной записи вне скоупа или несуществующий — 422 по полю, один текст; id владельца скоупа (parking_id) — 403 в middleware` and `Хендлер — валидация (тег binding, validate SDK не читает), маппинг, вызов; права — middleware; исключение: create сверяет parking_id из тела со скоупом — 403`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]` and `Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано` and `Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)` and `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **Why does `core/backlog.md` connect `Слои, домен, DTO` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Ревью и безопасность`, `Разработчик, тесты, Go-памятка`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._