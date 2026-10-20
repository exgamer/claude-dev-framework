# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 106 files · ~90,314 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1116 nodes · 4259 edges · 13 communities detected
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 196 edges (avg confidence: 0.83)
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
- [[_COMMUNITY_Community 12|Community 12]]

## God Nodes (most connected - your core abstractions)
1. `stacks/php/conventions.md` - 101 edges
2. `stacks/php/checklist.md` - 94 edges
3. `stacks/go/review-findings.md` - 82 edges
4. `репозиторий` - 78 edges
5. `stacks/php/review-findings.md` - 75 edges
6. `Транзакции` - 74 edges
7. `architecture/layers.md` - 70 edges
8. `stacks/php/structure.md` - 70 edges
9. `approaches/process/role-chain.md` - 69 edges
10. `approaches/process/review.md` - 67 edges

## Surprising Connections (you probably didn't know these)
- `Памятка stacks/go/checklist.md — целиком, всегда` --semantically_similar_to--> `Памятка stacks/go/checklist.md — целиком всегда; structure.md, README эталона — только по слоям своей задачи`  [INFERRED] [semantically similar]
  corpus/skills/dev-go/SKILL.md → corpus/agents/df-go.md
- `Памятка stacks/php/checklist.md — целиком, всегда` --semantically_similar_to--> `Памятка stacks/php/checklist.md — целиком всегда; structure.md и эталон — по слоям задачи`  [INFERRED] [semantically similar]
  corpus/skills/dev-php/SKILL.md → corpus/agents/df-php.md
- `В домене два интерфейса: Repository (БД) и CacheRepository (Redis), оба тупые Get/Set/Invalidate без решений` --semantically_similar_to--> `«Не найдено» — null/nil,nil; методов *OrFail и FindOrCreate нет`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/cache.md → corpus/architecture/layers.md
- `Промах кэша — (nil, nil), не ошибка; ошибка Redis не роняет сценарий — CaptureSoft и идём в БД` --semantically_similar_to--> `«Не найдено» — null/nil,nil; методов *OrFail и FindOrCreate нет`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/cache.md → corpus/architecture/layers.md
- `Каждый запрос — с таймаутом (Go context.WithTimeout, 10s по умолчанию; PHP — в конфиге)` --semantically_similar_to--> `Go: ErrRecordNotFound → nil, nil; таймаут context.WithTimeout(ctx, 10*time.Second) в каждом методе`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/stacks/go/go-sdk/infra.md

## Hyperedges (group relationships)
- **Правила платёжного колбэка (идемпотентность, подпись, кэш ошибок, outbox) в payments.md и Go review-findings** — rule_patterns_payments_idempotency_unique_txn, rule_patterns_payments_signature_before_state, rule_patterns_payments_no_cache_errors, rule_go_review_findings_pay_idempotency_unique, rule_go_review_findings_signature_each_gateway, rule_go_review_findings_no_cache_gateway_errors [INFERRED 0.90]
- **Правило транзакций O-8 повторено во многих файлах** — rule_transactions_multi_write_requires_tx, rule_decisions_o8_transactions, rule_go_checklist_tx_multi_write, rule_go_conventions_tx_atomicity, rule_gosdk_postgres_tx_multi_write, rule_gosdk_workflow_tx_rule, rule_gosdk_domain_command_tx_rule, rule_cross_domain_workflow_tx_same_rule, rule_review_tx_review_check [INFERRED 0.90]
- **Статус без потребителя — тупик** — rule_payments_status_has_consumer, rule_impact_analysis_status_without_consumer, rule_idempotency_final_status_not_overwritten [INFERRED 0.80]
- **O-19: сохранить контракт или по правилам, без переключателей** — rule_legacy_refactor_always_ask_keep_or_rules, rule_legacy_refactor_no_toggles, rule_stack_migration_contracts_by_user_answer, rule_stack_migration_no_toggles, rule_decisions_o19_contract_question, rule_rest_api_refactor_always_ask, rule_dev_architect_contracts_open_question, rule_dev_architect_feature_flag_only_new [INFERRED 0.90]
- **Лимит параллельных разработчиков 3 / refactor-migrate 5** — rule_role_chain_parallel_3_refactor_5, rule_dev_max_3_agents_one_tree, rule_dev_architect_concurrent_limit_3_5, rule_decisions_o20_task_size_limits [INFERRED 0.90]
- **Лимит параллельных разработчиков 3 (refactor/migrate — 5) в нескольких файлах** — rule_process_security_dev_agents_max3, rule_core_decisions_o20_task_size_devs, rule_dev_architect_skill_concurrent_limit_3_5 [INFERRED 0.85]
- **Подтверждение коммита/пуша и запрет подписи ИИ — одно правило в 5 файлах** — rule_principles_commit_push_each_time, rule_git_commit_push_confirm, rule_self_check_commit_after_confirmation, rule_role_chain_db_and_commit_explicit_yes, rule_principles_no_ai_coauthor, rule_git_no_ai_trailers, rule_style_no_ai_in_code_git, rule_subagent_no_ai_signature [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Общие шаги dev-go / dev-php / агентов** — file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, file_agents_df_go_md, file_agents_df_php_md [EXTRACTED 1.00]
- **Политика ACK/NACK consumer** — rule_rabbit_nil_ack_error_nack, rule_rabbit_deserialization_ack, rule_checklist_consumer_ack_nack, rule_go_tariff_readme_consumer_bad_payload_ack [INFERRED 0.85]
- **Механические grep-проверки формата и копий enum в Go и PHP** — file_stacks_go_review_checks_md, file_stacks_php_review_checks_md [INFERRED 0.80]
- **Скоуп владельца: 403 в middleware, чужая по ID — 404, без middleware — ошибка** — rule_tariff_readme_scope_parkingids_404, rule_tariff_readme_parkingids_without_mw_error, rule_go_security_idor_each_handler, rule_php_checklist_tenant_scope, rule_php_conv_permissions_middleware, rule_php_example_requesthelper_throws, rule_php_example_scope_no_default [INFERRED 0.85]
- **Правило транзакций O-8 в PHP повторено в 5 файлах** — file_stacks_php_checklist_md, file_stacks_php_conventions_md, file_stacks_php_mps_core_transactions_md, file_stacks_php_review_checks_md, file_stacks_php_review_tags_md [INFERRED 0.85]
- **Форма Command/Query: final, execute(), без базового класса ядра** — file_stacks_php_mps_core_commands_md, file_stacks_php_mps_core_queries_md, file_stacks_php_checklist_md, file_stacks_php_mps_core_capabilities_md, file_stacks_php_style_md, file_stacks_php_review_tags_md, file_stacks_php_review_findings_md [INFERRED 0.85]
- **Запрет связей и expanded — 4 файла** — rule_mpscore_model_no_relations, rule_mpscore_repository_no_expanded, rule_mpscore_service_no_expanded, rule_review_findings_no_relations_no_expanded [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (180): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/planning.md, approaches/process/role-chain.md (+172 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (167): architecture/cross-domain.md, architecture/layers.md, regulations/entity.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra.md, stacks/go/go-sdk/workflow.md, stacks/go/structure.md (+159 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (116): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/review-findings.md, stacks/php/examples/README.md, stacks/php/review-findings.md (+108 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.05
Nodes (98): approaches/patterns/concurrency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/checklist.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md, stacks/php/mps-core/transactions.md (+90 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.05
Nodes (89): agents/df-qa.md, approaches/process/self-check.md, approaches/process/testing.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md, skills/dev-qa/SKILL.md, stacks/php/checklist.md, stacks/php/mps-core/tests.md (+81 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (89): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/entrypoint.md, stacks/go/go-sdk/main.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md, stacks/php/mps-core/swagger.md (+81 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (76): approaches/patterns/async.md, approaches/patterns/idempotency.md, architecture/integrations.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/infra/http.md, stacks/php/mps-core/exceptions.md, stacks/php/mps-core/jobs.md (+68 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (73): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md (+65 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.05
Nodes (72): core/style.md, stacks/go/conventions.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Почему группы файлов: видно, от чего зависит модуль; 34 файла по одному интерфейсу в screen-gateway, Аббревиатуры целиком заглавными: ID, URL, HTTP, DTO, JSON, Уровень на ревью — [ВНИМАНИЕ], тег ai-style-text (комментарии — ai-style-comment) (+64 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.07
Nodes (58): approaches/patterns/cache.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/rules.md, stacks/php/README.md, Cache-aside inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; резолверы с фоновым обновлением отклонены, Go: gosdk-redis-core NewHelper[T] (SetStruct/GetStruct), удаление Unlink; PHP: getOrSet без тегов, SmartCache с тегами (+50 more)

### Community 10 - "Community 10"
Cohesion: 0.08
Nodes (51): approaches/process/impact-analysis.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, regulations/contracts.md, Интерфейс объявляет потребитель, не реализация; минимум методов, Внешний контракт описан (OpenAPI/AsyncAPI), DTO внешнего API не смешиваются с доменом, изменение — с версионированием, Интерфейс нужен при: 2+ реализациях, границе слоя, зависимости от внешнего мира, подмене в тестах, 1–5 методов — норма, 6–10 — пересмотреть, 10+ — декомпозировать (+43 more)

### Community 11 - "Community 11"
Cohesion: 0.12
Nodes (36): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Любое изменение БД — раздел «Схема БД» и пункт на согласование, Задача с миграцией — только при «да» на «Схему БД», Миграция: internal/migrations/{YYYYMMDDHHMMSS}_{что_делает}.go, ID = имя файла, Migrate и Rollback, Новая внешняя система — согласование до кода, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill (+28 more)

### Community 12 - "Community 12"
Cohesion: 0.31
Nodes (11): stacks/go/go-sdk/app.md, stacks/go/go-sdk/module.md, internal/app/app.go — только сборка: тип App, RegisterAndInitKernels, RegisterAndInitModules; без бизнес-логики и конфигурации, cmd/console — отдельный бинарник со своими ядрами (Postgres, Rabbit EnablePublisher, Console); HttpKernel и consumer-ы туда не входят, Модуль B берёт сервис модуля A через DI — A регистрируется первым (auth перед tariff), Без SentryKernel события errorreporter молча не уходят, DI — только внутри Init; a.Container не передавать в фабрики и сервисы, Рядом с module.go — фабрики repositories_factory, services_factory, handlers_factory, consumers_factory (workflows_factory не упомянута) (+3 more)

## Ambiguous Edges - Review These
- `База ревью MR — Target Branch MR, не дефолт (фичи в stage)` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Аудит: 4–8 параллельных агентов Explore без isolation, только чтение, запущенных одним сообщением` → `Агентов-разработчиков не больше 3 (в рефакторе 5) по planning.md, против 4–8 у аудита`  [AMBIGUOUS]
  corpus/approaches/process/security.md · relation: conflicts_with
- `DI — только в Bootstrap; resolve в бизнес-коде — [ОШИБКА]; PHP ServiceInterface всегда, Go — по необходимости` → `Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/migrations.md · relation: conflicts_with
- `п. 14: >1 модифицирующего запроса — один txManager.Exec; один — без транзакции (needless-transaction)` → `п. 6: вся бизнес-логика — только в сервисах и workflow`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `Проверено 2026-10-20 против gosdk-core v1.0.9, gosdk-db-core v1.0.10, http-request-builder v1.0.2 и др. (Go 1.26+)` → `Собирается в internal/app/bootstrap/{module}/workflows_factory.go, не services_factory.go`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/workflow.md · relation: conflicts_with
- `SetRequestTimeout на каждый запрос (default 30s)` → `Каждый DB-запрос — timeout 10s (кроме NewGormPaginatedHelper)`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/infra/postgres.md · relation: conflicts_with
- `Консольная команда: Use "{module-kebab}:{action}", тонкая — один вызов Command домена` → `Signature: {domain}:{module}:{action} через двоеточие`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/cli.md · relation: conflicts_with
- `Транзакция — только run(); не DB::transaction, не $repo->transaction` → `DB::transaction() — только в реализации менеджера транзакций (Infrastructure), при PostgreSQL-специфике`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md · relation: conflicts_with
- `Прогнаны csfix-validate, phpstan, test:run; вывод показан; не запускал — так и написано` → `Тесты в проекте локально не запускаются (битый RefreshDatabase) — сказать об этом`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Элемент внешнего массива (в т.ч. keyBy()-карта из репозитория) — только после проверки ключа ([ОШИБКА] array-key-unchecked)` → `Репозиторий: нет keyBy/map/сборки структур — маппинг в Service`  [AMBIGUOUS]
  corpus/stacks/php/conventions.md · relation: conflicts_with
- `utils ≥ 2.6.4: getOrSet вместо deprecated getOrCreate; SmartCache — utils ≥ 2.8` → `$cache->getOrCreate(...) вместо SmartCache/getOrSet — [ОШИБКА] (с поправкой по версии utils)`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `CRUDService сам оборачивает create/update/delete в транзакцию через getRepository()->transaction()` → `$this->repository->transaction() / $service->transaction() — антипаттерн на уровне Service/Command`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/transactions.md · relation: conflicts_with

## Knowledge Gaps
- **2 isolated node(s):** `Связи создают неявные запросы и соблазн обойти Repository`, `config_env`
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `База ревью MR — Target Branch MR, не дефолт (фичи в stage)` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Аудит: 4–8 параллельных агентов Explore без isolation, только чтение, запущенных одним сообщением` and `Агентов-разработчиков не больше 3 (в рефакторе 5) по planning.md, против 4–8 у аудита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `DI — только в Bootstrap; resolve в бизнес-коде — [ОШИБКА]; PHP ServiceInterface всегда, Go — по необходимости` and `Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `п. 14: >1 модифицирующего запроса — один txManager.Exec; один — без транзакции (needless-transaction)` and `п. 6: вся бизнес-логика — только в сервисах и workflow`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Проверено 2026-10-20 против gosdk-core v1.0.9, gosdk-db-core v1.0.10, http-request-builder v1.0.2 и др. (Go 1.26+)` and `Собирается в internal/app/bootstrap/{module}/workflows_factory.go, не services_factory.go`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `SetRequestTimeout на каждый запрос (default 30s)` and `Каждый DB-запрос — timeout 10s (кроме NewGormPaginatedHelper)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Консольная команда: Use "{module-kebab}:{action}", тонкая — один вызов Command домена` and `Signature: {domain}:{module}:{action} через двоеточие`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._