# Graph Report - dev-framework rules  (2026-10-21)

## Corpus Check
- 108 files · ~110,411 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1165 nodes · 4685 edges · 10 communities detected
- Extraction: 96% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 160 edges (avg confidence: 0.81)
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
- [[_COMMUNITY_Community 9|Community 9]]

## God Nodes (most connected - your core abstractions)
1. `stacks/php/checklist.md` - 125 edges
2. `approaches/process/review-tags.md` - 102 edges
3. `stacks/php/conventions.md` - 101 edges
4. `stacks/go/checklist.md` - 99 edges
5. `approaches/process/review.md` - 94 edges
6. `репозиторий` - 88 edges
7. `skills/dev-review/SKILL.md` - 86 edges
8. `транзакции` - 84 edges
9. `stacks/php/structure.md` - 82 edges
10. `architecture/layers.md` - 75 edges

## Surprising Connections (you probably didn't know these)
- `У каждого нового статуса платежа есть потребитель (крон, consumer, workflow), показать в MR; тег status-without-consumer` --semantically_similar_to--> `Новый статус без потребителя (крон, consumer, переход) — status-without-consumer`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `Две остановки: после документа и после плана; коммит и пуш — по отдельному «да» на каждый` --semantically_similar_to--> `Действия с БД и коммиты/пуш — только по явному «да» на каждое`  [INFERRED] [semantically similar]
  corpus/approaches/process/mr-threads.md → corpus/approaches/process/role-chain.md
- `repository-multi-query [ОШИБКА]: публичный метод репозитория выполняет больше одного запроса (пагинатор и пакетный upsert — один)` --semantically_similar_to--> `Метод репозитория — один запрос; порядок и txManager.Exec — в сервисе/Command/workflow`  [INFERRED] [semantically similar]
  corpus/approaches/process/review-tags.md → corpus/stacks/go/checklist.md
- `access-check-outside-middleware [ОШИБКА]: роль/право проверяется в контроллере, хендлере или домене, а не middleware` --semantically_similar_to--> `Роль/право — middleware на маршруте (RequirePermissionMiddleware); проверок ролей в хендлере и домене нет`  [INFERRED] [semantically similar]
  corpus/approaches/process/review-tags.md → corpus/stacks/go/checklist.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Зеркальные пункты памяток Go/PHP и каталога тегов** — rule_review_tags_repository_multi_query, rule_go_checklist_repo_one_query, rule_php_checklist_repo_one_query, rule_review_tags_multi_write_without_transaction, rule_go_checklist_tx_multi_write, rule_php_checklist_tx_multi_write, rule_review_tags_status_in_update_request, rule_go_checklist_status_not_in_put, rule_php_checklist_status_not_in_put [INFERRED 0.80]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Скоуп записи: тип с явным «без ограничения», 404 по ID, 403 по id владельца** — rule_layers_scope_by_id_404, rule_go_checklist_scope_type_explicit_all, rule_go_conventions_scope_type_parking_scope, rule_php_examples_readme_scope_null_explicit, rule_layers_cache_by_id_scope_check [INFERRED 0.85]
- **Границы Common: словарь без зависимостей и тест границ** — rule_layers_common_vocabulary_only, rule_go_structure_common_std_only, rule_php_examples_readme_common_boundary_test, rule_php_structure_core_technical_only, rule_go_review_checks_enum_pairs_duplicated [INFERRED 0.85]
- **Правило O-8 «транзакция только при >1 записи» в 5 местах** — rule_decisions_o8_tx_only_justified, rule_go_conventions_p17_tx_more_than_one_write, rule_go_checklist_p14_tx_more_than_one, rule_php_checklist_p19_tx_more_than_one_run, rule_go_sdk_domain_command_tx_more_than_one [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Не пробрасываемая ошибка — в трекер, не только в лог (Go и PHP)** — rule_go_conventions_soft_error_errorreporter, rule_go_review_findings_logger_instead_errorreporter, rule_php_conventions_soft_error_report [INFERRED 0.90]
- **Памятка PHP, каталог тегов PHP и структура описывают одни и те же правила (DI, env, статус, тесты)** — file_stacks_php_checklist_md, file_stacks_php_review_tags_md, file_stacks_php_structure_md [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (180): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/planning.md (+172 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (159): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/postgres.md (+151 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (157): regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md (+149 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (146): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/self-check.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md (+138 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (136): architecture/cross-domain.md, architecture/integrations.md, architecture/layers.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md (+128 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (127): agents/df-security.md, approaches/patterns/idempotency.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/exceptions.md, stacks/go/review-checks.md (+119 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (84): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, approaches/process/testing.md, regulations/contracts.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, stacks/php/structure.md (+76 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (73): approaches/patterns/cache.md, core/principles.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/rules.md, stacks/php/mps-core/service.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены (+65 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.05
Nodes (71): core/style.md, stacks/go/checklist.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, В счёт «4+ аргумента» не входят t *testing.T, b *testing.B, f *testing.F, ctx context.Context, В: docblock класса — одна строка по-русски + @author; нигде нет Claude/ИИ как автора (+63 more)

### Community 9 - "Community 9"
Cohesion: 0.14
Nodes (32): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, О: миграция обратно совместима (expand/contract), FK явно, откат описан; строго по «Схеме БД» design.md, Миграция строго по «Схеме БД» design.md; иначе вопрос, Миграция строго по «Схеме БД» design.md; иначе вопрос, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill, Миграция данных — отдельной миграцией, обратимой где возможно (+24 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `side-effect-without-outbox [ОШИБКА]: эффект с гарантией доставки (платёжный путь — всегда) fire-and-forget вместо outbox` → `Уведомление партнёра — после коммита без гарантии доставки (допущение примера); нужна гарантия — outbox`  [AMBIGUOUS]
  corpus/stacks/go/examples/tariff/README.md · relation: conflicts_with
- `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям` → `Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
- `Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic` → `internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `page=0 или не передан → 1 при разборе запроса, до расчёта смещения (беззнаковое переполнение)` → `О: id → integer|gt:0|max bigint, id пути в Request без ограничения маршрута (0/-1/abc → 422); page min:0 (0 — первая), max Page::MAX_PAGE = 10000`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `Служебные пути оркестратора (/healthcheck/*, метрики) — не ресурс API, под схему /api/… не подпадают` → `Healthcheck не пишется: HealthCheck из mps/utils (GET /health/liveness, /health/readiness)`  [AMBIGUOUS]
  corpus/stacks/php/structure.md · relation: conflicts_with
- `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` → `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `URL: /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; модуль = сущность не повторяется; kebab-case, множественное число` → `Пробы healthcheck: сбой — 503 без Sentry, причина — logger.Warning; путь /healthcheck/* вне схемы /api намеренно`  [AMBIGUOUS]
  corpus/stacks/go/examples/tariff/README.md · relation: conflicts_with
- `Источник и версия Go SDK — из go.mod без вопроса; спросить пользователя для нового проекта/смены версии/нового пакета; записать в design.md «SDK» (O-4)` → `Версия ядра mps/core — из composer.lock, у пользователя не спрашивать (O-4)`  [AMBIGUOUS]
  corpus/stacks/php/conventions.md · relation: conflicts_with
- `per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)` → `per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/infra/postgres.md · relation: conflicts_with
- `Консоль — свой bootstrap internal/console/bootstrap/{domain}/{module}/; серверный bootstrap в консоль не подключается` → `Одна настройка — одно место: env-переменную читает config.go владельца, остальные — через Load{Name}() (консоль импортирует серверный пакет ради LoadMaxAttempts)`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `О: устаревшее API ядра (RabbitManager::putIn, QueryFilters V1, FileManager::upload; getOrCreate при utils ≥ 2.6.4) — нет` → `deprecated-core-api [ОШИБКА]: RabbitManager::putIn, QueryFilters V1, FileManager::upload, setMessagePrefix; getOrCreate/getCacheKeyByParts при utils ≥ 2.6.4`  [AMBIGUOUS]
  corpus/stacks/php/review-tags.md · relation: conflicts_with
- `О: прогнаны csfix-validate, phpstan (уровень ≥ 5, шум mps — ignoreErrors, не @phpstan-ignore), test:run; не запускал — так и написано` → `В: новые @phpstan-ignore*, markTestSkipped, #[CodeCoverageIgnore], понижение level, ignoreErrors — только с причиной рядом (check-silenced)`  [AMBIGUOUS]
  corpus/stacks/php/checklist.md · relation: conflicts_with

## Knowledge Gaps
- **9 isolated node(s):** `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, контекст оркестратора, расход квоты; последовательные волны ограничивают выигрыш`, `конфигурация сервиса`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения`, `Длинные копии правила в 5–9 местах расходятся при каждом уточнении (O-23)` (+4 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `side-effect-without-outbox [ОШИБКА]: эффект с гарантией доставки (платёжный путь — всегда) fire-and-forget вместо outbox` and `Уведомление партнёра — после коммита без гарантии доставки (допущение примера); нужна гарантия — outbox`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям` and `Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic` and `internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `page=0 или не передан → 1 при разборе запроса, до расчёта смещения (беззнаковое переполнение)` and `О: id → integer|gt:0|max bigint, id пути в Request без ограничения маршрута (0/-1/abc → 422); page min:0 (0 — первая), max Page::MAX_PAGE = 10000`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Служебные пути оркестратора (/healthcheck/*, метрики) — не ресурс API, под схему /api/… не подпадают` and `Healthcheck не пишется: HealthCheck из mps/utils (GET /health/liveness, /health/readiness)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._