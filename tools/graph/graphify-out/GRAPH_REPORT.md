# Graph Report - dev-framework rules  (2026-10-23)

## Corpus Check
- 111 files · ~121,339 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1273 nodes · 5054 edges · 10 communities detected
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 178 edges (avg confidence: 0.83)
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
- [[_COMMUNITY_Community 8|Community 8]]
- [[_COMMUNITY_Community 9|Community 9]]

## God Nodes (most connected - your core abstractions)
1. `core/backlog.md` - 116 edges
2. `stacks/php/conventions.md` - 98 edges
3. `stacks/php/checklist.md` - 94 edges
4. `транзакции` - 85 edges
5. `skills/dev-php/SKILL.md` - 85 edges
6. `approaches/process/review-tags.md` - 82 edges
7. `stacks/php/review-findings.md` - 79 edges
8. `core/decisions.md` - 77 edges
9. `stacks/go/examples/tariff/README.md` - 77 edges
10. `stacks/go/checklist.md` - 76 edges

## Surprising Connections (you probably didn't know these)
- `Очередь, Job::dispatch, HTTP, файлы, сброс кэша — после записи и вне транзакции` --semantically_similar_to--> `Побочные эффекты после фиксации платежа — через outbox, не fire-and-forget`  [INFERRED] [semantically similar]
  corpus/stacks/php/checklist.md → corpus/approaches/patterns/payments.md
- `Баг-фикс контракт не меняет — граница /dev-fix` --semantically_similar_to--> `Баг-фикс /dev-fix в пределах контракта — без design; новые права/роли/middleware — изменение контракта, /dev с design`  [INFERRED] [semantically similar]
  corpus/approaches/process/impact-analysis.md → corpus/approaches/process/security.md
- `Субагент не задаёт вопросы пользователю — возвращает оркестратору: вопрос, 2–3 варианта, рекомендация` --semantically_similar_to--> `Ни один пробел/спор не решён молча — строка в docs/tasks/<ключ>/rules-log.md`  [INFERRED] [semantically similar]
  corpus/approaches/process/subagent.md → corpus/approaches/process/self-check.md
- `Обёртку {success, data} даёт middleware стека (Go FormattedResponseMiddleware, PHP ApiResponseMiddleware), не контроллер (response-double-wrap)` --semantically_similar_to--> `Тонкая точка входа: биндинг → DTO → один вызов сервиса/Command/workflow → ответ`  [INFERRED] [semantically similar]
  corpus/regulations/rest-api.md → corpus/architecture/layers.md
- `Ранний выход и форма кода core/style.md действуют и для Python в тестах` --semantically_similar_to--> `Ранний выход вместо else после return (else-after-return); для всех стеков`  [INFERRED] [semantically similar]
  corpus/stacks/qa-playwright/conventions.md → corpus/core/style.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **До 3 параллельных разработчиков (до 5 в refactor/migrate)** — rule_planning_max_3_devs, rule_dev_max_3_agents, rule_planning_fresh_agent_per_task, rule_dev_fresh_agent_5min [INFERRED 0.90]
- **Явное «да» на БД/коммит/пуш** — rule_role_chain_db_commit_explicit_yes, rule_skill_dev_commit_push_explicit_yes, rule_checklist_db_actions_yes, rule_checklist_commit_push_yes, rule_skill_dev_qa_db_reset_explicit_yes, rule_release_go_bump_yes_per_step [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Метод репозитория — один запрос** — rule_layers_repo_one_query, rule_review_tags_repo_one_query, rule_checklist_repo_one_query, rule_conventions_repo_one_query [INFERRED 0.90]
- **Правило транзакций O-8 в решениях, памятках и конвенциях** — rule_decisions_o8_transactions, rule_go_checklist_tx_more_than_one, rule_go_conventions_tx_atomic, rule_php_checklist_tx_run [INFERRED 0.90]
- **Лимит параллельных разработчиков 3 / 5** — rule_decisions_o20_task_size, rule_dev_max_3_agents, rule_dev_architect_limit_3_5 [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Коммит/пуш только по «да» на каждый** — rule_git_no_commit_without_confirm, rule_dev_commit_push_only_yes, rule_dev_rule_commit_only_confirm, rule_checklist_commit_push_yes, rule_planning_same_worktree [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **TransactionManagerInterface вместо repository->transaction** — rule_service_no_repo_transaction, rule_transactions_inject_tm_run, rule_review_checks_no_repo_transaction, rule_review_findings_tx_via_manager [INFERRED 0.90]
- **Запись только через DTO (п. 11a)** — rule_repository_write_dto_not_array, rule_review_findings_write_array_instead_of_dto, rule_service_crudservice_read_only, rule_security_mass_assignment_guarded_false [INFERRED 0.85]
- **Доступ — middleware и скоуп, не Request/контроллер** — rule_http_access_in_middleware, rule_review_findings_access_in_middleware, rule_repository_scope_key_mandatory, rule_security_tenant_scope_mandatory [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.02
Nodes (220): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, approaches/process/analysis.md, approaches/process/bugfix.md, approaches/process/code-navigation.md (+212 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (191): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, core/backlog.md, core/decisions.md, core/rules-evolution.md, regulations/code-review.md (+183 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (157): regulations/entity.md, regulations/rest-api.md, stacks/go/checklist.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/data-objects.md (+149 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (149): approaches/patterns/async.md, approaches/patterns/cache.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/entrypoint/rabbit.md (+141 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (142): approaches/patterns/transactions.md, architecture/cross-domain.md, architecture/layers.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/go/review-checks.md, stacks/go/structure.md, stacks/php/mps-core/commands.md (+134 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (111): approaches/patterns/migrations.md, approaches/process/self-check.md, core/principles.md, regulations/technical-approval.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/capabilities.md, stacks/php/checklist.md (+103 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (93): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/review-findings.md, stacks/go/security.md, stacks/php/security.md, Новый роут без auth: review.md — mr-hygiene [ВНИМАНИЕ], каталог — missing-auth-middleware [БЕЗОПАСНОСТЬ] (+85 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (82): architecture/integrations.md, core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md (+74 more)

### Community 8 - "Community 8"
Cohesion: 0.04
Nodes (75): regulations/service-requirements.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/domain.md, stacks/php/mps-core/exceptions.md, stacks/php/mps-core/rules.md (+67 more)

### Community 9 - "Community 9"
Cohesion: 0.08
Nodes (53): approaches/process/testing.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, stacks/php/structure.md, stacks/qa-playwright/conventions.md, Интеграционные прогоны на общей тестовой БД — только по очереди, оркестратор проверяет, что другой прогон не идёт, Unit-тесты — вместе с кодом; без тестов на новую логику задача не готова, QA: красное не сходится за 3 попытки — остановиться и показать (+45 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `FK объявляется явно с поведением UPDATE/DELETE (по умолчанию RESTRICT); колонки под фильтры/сортировки/уникальность — с индексом в той же миграции` → `Исключение: низкоселективный фильтр внутри выборки, суженной индексом скоупа, индекса не требует (предлагается)`  [AMBIGUOUS]
  corpus/approaches/patterns/migrations.md · relation: conflicts_with
- `Параметры списка: page 1, per_page 30, page >10000 или отрицательный → 422` → `IndexRequest: page sometimes min:0 max Page::MAX_PAGE; per_page sometimes between:1,MAX_PER_PAGE`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/http.md · relation: conflicts_with
- `Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]` → `Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `ServiceProvider модуля — только если модуль регистрирует что-то сверх definitions.php домена; у большинства нет` → `Биндинги DI — только в definitions.php (полный перечень мест); биндинг вне — di-outside-definitions`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/domain.md · relation: conflicts_with
- `Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано` → `Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/jobs.md · relation: conflicts_with
- `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)` → `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md · relation: conflicts_with

## Knowledge Gaps
- **10 isolated node(s):** `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Флаг — две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Бэклог — чтобы находки не всплывали по одной в каждой следующей правке`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения`, `Знание о статусах в одном месте: сервис, сравнивающий статусы сам, расходится с соседним при первой правке` (+5 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `FK объявляется явно с поведением UPDATE/DELETE (по умолчанию RESTRICT); колонки под фильтры/сортировки/уникальность — с индексом в той же миграции` and `Исключение: низкоселективный фильтр внутри выборки, суженной индексом скоупа, индекса не требует (предлагается)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Параметры списка: page 1, per_page 30, page >10000 или отрицательный → 422` and `IndexRequest: page sometimes min:0 max Page::MAX_PAGE; per_page sometimes between:1,MAX_PER_PAGE`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]` and `Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `ServiceProvider модуля — только если модуль регистрирует что-то сверх definitions.php домена; у большинства нет` and `Биндинги DI — только в definitions.php (полный перечень мест); биндинг вне — di-outside-definitions`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано` and `Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)` and `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._