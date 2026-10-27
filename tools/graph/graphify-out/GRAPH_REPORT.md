# Graph Report - dev-framework rules  (2026-10-27)

## Corpus Check
- 111 files · ~130,796 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1205 nodes · 4949 edges · 10 communities detected
- Extraction: 95% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 218 edges (avg confidence: 0.83)
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
1. `core/backlog.md` - 109 edges
2. `stacks/php/conventions.md` - 103 edges
3. `stacks/php/checklist.md` - 97 edges
4. `stacks/go/checklist.md` - 95 edges
5. `Транзакции` - 88 edges
6. `core/principles.md` - 77 edges
7. `Репозиторий` - 76 edges
8. `approaches/process/review-tags.md` - 73 edges
9. `skills/dev-php/SKILL.md` - 73 edges
10. `stacks/go/conventions.md` - 73 edges

## Surprising Connections (you probably didn't know these)
- `TTL — константа в кэш-репозитории (2*time.Hour); фоновых пулеров/горутин обновления нет` --conflicts_with--> `Порядок: ограничение БД → FOR UPDATE → оптимистическая версия → распределённая блокировка; mutex не защищает`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/concurrency.md
- `Секреты не кладутся в общий Redis ни открыто, ни зашифрованно — in-process кэш (secret-in-shared-cache)` --semantically_similar_to--> `Секреты шлюза — из конфига/vault, в БД зашифрованы, не в логах и не в общем Redis`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/payments.md
- `Переход статуса платежа условный; финальный не перезаписывается (race-on-status)` --semantically_similar_to--> `Смена статуса — условный UPDATE WHERE status = :expected или FOR UPDATE (race-on-status)`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/patterns/concurrency.md
- `«Принять риск гонки» решает только пользователь через «Открытые вопросы» design.md (race-risk-accepted-silently)` --semantically_similar_to--> `Отличие от легаси по данным/ответам — решение пользователя, без ответа — 1:1 с легаси`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/concurrency.md → corpus/approaches/process/legacy-refactor.md
- `Не баг-фикс: миграция/схема, изменение контракта, >1 модуля или ~>10 файлов, другой сервис — стоп и /dev` --semantically_similar_to--> `Любое изменение схемы (и новая таблица) — согласование до кода, формат «Схема БД»`  [INFERRED] [semantically similar]
  corpus/approaches/process/bugfix.md → corpus/approaches/patterns/migrations.md

## Hyperedges (group relationships)
- **Решение за пользователем: риск гонки, контракт легаси, споры MR** — rule_concurrency_risk_acceptance_user, rule_legacy_refactor_contract_always_ask, rule_mr_threads_no_silent_decisions [INFERRED 0.75]
- **Контракт репозитория: один запрос, не найдено — null, запрос целиком** — rule_layers_repo_method_one_query, rule_conventions_one_terminal_operation, rule_layers_not_found_null_no_orfail, rule_conventions_no_orfail_wrappers, rule_exceptions_repository_no_not_found, rule_layers_query_visible_whole, rule_conventions_query_whole_laravel [INFERRED 0.85]
- **Подтверждение коммита/пуша и действий с БД — одно правило в 5 файлах** — rule_principles_git_confirm_each, rule_principles_db_actions_permission, rule_role_chain_db_commit_explicit_yes, rule_dev_commit_only_after_yes, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_go_db_no_run_without_yes, rule_dev_php_db_no_run_without_yes [INFERRED 0.85]
- **Предел 3 круга ревью/исправлений** — rule_principles_loop_max_3_rounds, rule_role_chain_review_max_3_rounds, rule_dev_review_max_3_rounds_delta, rule_backlog_d3_usage_reduction [INFERRED 0.80]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **SKILL dev-go и dev-php — зеркальные шаги (правишь один — проверь второй)** — file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, rule_dev_go_small_fix_without_design, rule_dev_php_small_fix_without_design, rule_dev_go_checklist_always_structure_by_layer, rule_dev_php_checklist_always_structure_by_layer, rule_dev_go_threats_measures_by_files, rule_dev_php_threats_measures_by_files [INFERRED 0.90]
- **Consumer: временная ошибка — повтор, постоянная — фиксация без повтора (Go и PHP)** — rule_rabbit_go_nil_ack_error_nack, rule_rabbit_go_permanent_error_ack, rule_jobs_php_temp_vs_permanent, rule_exceptions_php_consumer_temp_only, rule_readme_php_examples_consumer_delays [INFERRED 0.85]
- **TransactionManagerInterface вместо repository->transaction** — rule_service_no_repo_transaction, rule_transactions_inject_tm_run, rule_review_checks_no_repo_transaction, rule_review_findings_tx_via_manager [INFERRED 0.90]
- **Доступ — middleware и скоуп, не Request/контроллер** — rule_http_access_in_middleware, rule_review_findings_access_in_middleware, rule_repository_scope_key_mandatory, rule_security_tenant_scope_mandatory [INFERRED 0.80]
- **DI только через definitions.php (PHP)** — rule_rules_di_only_definitions, rule_structure_di_places_list, rule_review_tags_di_outside_def_error [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (191): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, approaches/process/analysis.md, approaches/process/bugfix.md, approaches/process/impact-analysis.md (+183 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (173): approaches/process/code-navigation.md, approaches/process/mr-threads.md, approaches/process/self-check.md, core/decisions.md, core/principles.md, core/rules-evolution.md, regulations/code-review.md, regulations/git.md (+165 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (160): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/postgres.md (+152 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (143): architecture/cross-domain.md, architecture/integrations.md, architecture/layers.md, regulations/contracts.md, regulations/entity.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/http.md (+135 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (142): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md, stacks/php/mps-core/swagger.md (+134 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.03
Nodes (141): approaches/patterns/cache.md, regulations/service-requirements.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/infra/redis.md, stacks/go/go-sdk/module.md (+133 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.06
Nodes (81): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/checklist.md, stacks/go/security.md, stacks/php/security.md, B-125: роут без auth — в review.md mr-hygiene [ВНИМАНИЕ], в каталоге missing-auth-middleware [БЕЗОПАСНОСТЬ] (+73 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.07
Nodes (70): agents/df-review.md, approaches/process/review.md, approaches/process/review-tags.md, core/backlog.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md, stacks/go/review-tags.md, stacks/php/README.md (+62 more)

### Community 8 - "Community 8"
Cohesion: 0.05
Nodes (69): core/style.md, stacks/go/conventions.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Глубина блоков ≤ 2, вызов не вкладывается в вызов, лямбда — одна строка, Docblock класса — одна строка по-русски + @author; без «ИИ-воды», 4+ аргумента (не конструктор) → DTO; допустимо до 3 позиционных + DTO опций, у приватного шага до ~5 (+61 more)

### Community 9 - "Community 9"
Cohesion: 0.11
Nodes (35): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Миграции катит консольная команда (NewMigrator…Up), kernel миграции не катит, Миграция строго по «Схеме БД» design.md; обратно совместима (expand/contract), Любое изменение структуры БД — раздел «Схема БД» и вопрос на согласование, Миграция — строго по «Схеме БД» design.md; другое поле — вопрос, Миграция — строго по «Схеме БД» design.md; другое поле — вопрос (+27 more)

## Ambiguous Edges - Review These
- `TTL — константа в кэш-репозитории (2*time.Hour); фоновых пулеров/горутин обновления нет` → `Порядок: ограничение БД → FOR UPDATE → оптимистическая версия → распределённая блокировка; mutex не защищает`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Не больше 3 разработчиков одновременно (+1 QA, +1 ревьюер); в refactor/migrate — до 5` → `Аудит — 4–8 параллельных Explore-агентов только на чтение, не «не больше 3»`  [AMBIGUOUS]
  corpus/approaches/process/security.md · relation: conflicts_with
- `В бэклог: «было и до правки» [БЕЗОПАСНОСТЬ]/[ОШИБКА] из Шага 3а и [ОШИБКА]/[ВНИМАНИЕ] из полной сверки` → `Найденное вне правки: в бэклог фреймворка — только [БЕЗОПАСНОСТЬ] и [ОШИБКА], [ВНИМАНИЕ] — списком в отчёт`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `D-3: расход 46–120 млн токенов на задачу, оркестратор 22–36%; не делать: предел шагов ниже 120, лимит кругов ревью` → `Цикл «найти — исправить»: предел 3 круга, повторный — по дельте; [ИНФО] не повод для круга и не строка бэклога`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `Пример: CanCancel() bool + Cancel() error` → `CanMakeDefault(): отключённый — ErrTariffInactive (проверка допустимости с ошибкой)`  [AMBIGUOUS]
  corpus/regulations/entity.md · relation: conflicts_with
- `go — только в workflow/entrypoint с lifecycle (unbounded-goroutines); после записи — outbox` → `RabbitPublisher: таймаут публикации 5 с в адаптере (SDK ctx не принимает); дубль отсекает получатель по event_id`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `Не использовать глобальные переменные` → `Sentinel-ошибки — в errors.go (var Err…)`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `4+ аргумента (не конструктор) → DTO; допустимо до 3 позиционных + DTO опций, у приватного шага до ~5` → `sign/verify — не больше трёх аргументов (core/style.md)`  [AMBIGUOUS]
  corpus/stacks/php/checklist.md · relation: conflicts_with
- `CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)` → `CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md · relation: conflicts_with
- `Нарушение без тега — [ОШИБКА] временно (O-24)` → `Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with

## Knowledge Gaps
- **14 isolated node(s):** `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Почему 3: одно рабочее дерево, нарезка без пересечений, контекст оркестратора, квота (решение 2026-10-07)`, `Каждый шаг перечитывает весь контекст: агенты со 100+ шагами (23% запусков) дали 2/3 расхода токенов`, `worktree от ветки не увидел бы незакоммиченные предыдущие волны`, `Почему свежие агенты на роль: одна сессия на все роли переполняет контекст, при сжатии первыми теряются детали правил` (+9 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `TTL — константа в кэш-репозитории (2*time.Hour); фоновых пулеров/горутин обновления нет` and `Порядок: ограничение БД → FOR UPDATE → оптимистическая версия → распределённая блокировка; mutex не защищает`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Не больше 3 разработчиков одновременно (+1 QA, +1 ревьюер); в refactor/migrate — до 5` and `Аудит — 4–8 параллельных Explore-агентов только на чтение, не «не больше 3»`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `В бэклог: «было и до правки» [БЕЗОПАСНОСТЬ]/[ОШИБКА] из Шага 3а и [ОШИБКА]/[ВНИМАНИЕ] из полной сверки` and `Найденное вне правки: в бэклог фреймворка — только [БЕЗОПАСНОСТЬ] и [ОШИБКА], [ВНИМАНИЕ] — списком в отчёт`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `D-3: расход 46–120 млн токенов на задачу, оркестратор 22–36%; не делать: предел шагов ниже 120, лимит кругов ревью` and `Цикл «найти — исправить»: предел 3 круга, повторный — по дельте; [ИНФО] не повод для круга и не строка бэклога`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Пример: CanCancel() bool + Cancel() error` and `CanMakeDefault(): отключённый — ErrTariffInactive (проверка допустимости с ошибкой)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `go — только в workflow/entrypoint с lifecycle (unbounded-goroutines); после записи — outbox` and `RabbitPublisher: таймаут публикации 5 с в адаптере (SDK ctx не принимает); дубль отсекает получатель по event_id`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Не использовать глобальные переменные` and `Sentinel-ошибки — в errors.go (var Err…)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._