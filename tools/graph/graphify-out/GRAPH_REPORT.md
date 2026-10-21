# Graph Report - dev-framework rules  (2026-10-21)

## Corpus Check
- 108 files · ~109,474 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1092 nodes · 4373 edges · 12 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 123 edges (avg confidence: 0.8)
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
- [[_COMMUNITY_Community 10|Community 10]]
- [[_COMMUNITY_Community 11|Community 11]]

## God Nodes (most connected - your core abstractions)
1. `stacks/php/conventions.md` - 126 edges
2. `approaches/process/review.md` - 93 edges
3. `stacks/go/checklist.md` - 88 edges
4. `skills/dev-review/SKILL.md` - 86 edges
5. `репозиторий` - 85 edges
6. `approaches/process/review-tags.md` - 79 edges
7. `транзакции` - 76 edges
8. `architecture/layers.md` - 75 edges
9. `stacks/php/checklist.md` - 74 edges
10. `core/principles.md` - 73 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции с блокировкой — только короткая работа, без внешних вызовов`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/concurrency.md
- `Доставка outbox — Command домена (однодоменная), не workflow; лимит попыток из конфига` --semantically_similar_to--> `Workflow собирается в workflows_factory.go, не в services_factory (решение 2026-10-07)`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/outbox.md → corpus/stacks/go/review-findings.md
- `У каждого нового статуса платежа есть потребитель (крон, consumer, workflow), показать в MR; тег status-without-consumer` --semantically_similar_to--> `Новый статус без потребителя (крон, consumer, переход) — status-without-consumer`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Тест на каждый платёжный сценарий — e2e по шлюзам (tests/<шлюз>/ в Go, tests/Feature/… в PHP), включая повтор колбэка и неверную подпись` --semantically_similar_to--> `Сценарий без теста при живых тестах в проекте — законное замечание; тесты не запускаются — сказать, не предлагать как проверку`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Две остановки: после документа и после плана; коммит и пуш — по отдельному «да» на каждый` --semantically_similar_to--> `Действия с БД и коммиты/пуш — только по явному «да» на каждое`  [INFERRED] [semantically similar]
  corpus/approaches/process/mr-threads.md → corpus/approaches/process/role-chain.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Скоуп записи: тип с явным «без ограничения», 404 по ID, 403 по id владельца** — rule_layers_scope_by_id_404, rule_go_checklist_scope_type_explicit_all, rule_go_conventions_scope_type_parking_scope, rule_php_examples_readme_scope_null_explicit, rule_layers_cache_by_id_scope_check [INFERRED 0.85]
- **Границы Common: словарь без зависимостей и тест границ** — rule_layers_common_vocabulary_only, rule_go_structure_common_std_only, rule_php_examples_readme_common_boundary_test, rule_php_structure_core_technical_only, rule_go_review_checks_enum_pairs_duplicated [INFERRED 0.85]
- **Правило O-8 «транзакция только при >1 записи» в 5 местах** — rule_decisions_o8_tx_only_justified, rule_go_conventions_p17_tx_more_than_one_write, rule_go_checklist_p14_tx_more_than_one, rule_php_checklist_p19_tx_more_than_one_run, rule_go_sdk_domain_command_tx_more_than_one [INFERRED 0.85]
- **Переход состояния: своя Command + условный UPDATE + метод модели** — rule_entity_transition_own_command, rule_entity_conditional_update_422, rule_go_checklist_put_patch_no_status, rule_go_tariff_readme_set_default_own_endpoint, rule_php_examples_readme_state_not_fillable [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (174): architecture/cross-domain.md, architecture/layers.md, regulations/entity.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/module.md, stacks/go/go-sdk/workflow.md (+166 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (154): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/planning.md (+146 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (148): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md (+140 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (114): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/transactions.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/postgres.md (+106 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.05
Nodes (80): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/review-checks.md, stacks/go/review-findings.md, stacks/go/security.md, B-62: layers.md «Роли и права» расходится с эталонами (права по действиям) — сослаться на решение в README эталонов (+72 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.06
Nodes (77): approaches/patterns/cache.md, approaches/patterns/migrations.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/migrations.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены, Инвалидация — при записи, в том же сценарии, что меняет данные (+69 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (70): regulations/service-requirements.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/main.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/exceptions.md, stacks/php/mps-core/rules.md, stacks/php/mps-core/swagger.md (+62 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (65): core/style.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, B-80: в счёт «4+ аргумента» не считать t *testing.T, b, f, ctx context.Context (сейчас считаются по core/style.md), Сообщения клиенту по-русски (ошибки полей и бизнес-ошибки); общий message 422 — как в проекте, UI-тесты: Python + Playwright + pytest (POM) (+57 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (65): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/php/mps-core/http.md, B-83: page=0 или не передан → 1 при разборе запроса, до расчёта смещения (переполнение беззнакового), D-8: два фильтра списка по одной колонке (status и archived) — правила сочетания нет, Проверки значений DTO — статический final {E}DtoValidator::validate (P-8), ошибки ['field' => ['сообщение']] по-русски; inline-проверки — invariants-inline, Фильтры IndexRequest = поля {E}FilterDto = условия метода списка (request-filter-not-applied), FormRequest — только формат: id gt:0 и max bigint (Request::ID_MAX), page max Page::MAX_PAGE = 10000, max для строк/массивов; без проверки доступа (+57 more)

### Community 9 - "Community 9"
Cohesion: 0.07
Nodes (63): approaches/process/self-check.md, core/principles.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md, stacks/go/checklist.md, stacks/go/conventions.md, stacks/go/go-sdk/capabilities.md, Версия ядра mps/core + mps/utils — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor (+55 more)

### Community 10 - "Community 10"
Cohesion: 0.08
Nodes (57): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, approaches/process/testing.md, regulations/contracts.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах (+49 more)

### Community 11 - "Community 11"
Cohesion: 0.13
Nodes (25): architecture/integrations.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, Конфиг адаптера через конструктор; нет обязательного — InvalidConfigurationAppException, не молчаливый дефолт, Порт и адаптер — по роли, не по поставщику ({Provider}{Product}Repository/Notifier); *Gateway/*Client/имя системы в порте — нет; так же в Go п.14, Элемент внешнего массива — только после проверки ключа (array-key-unchecked); интеграция — DTO обязательно; без ?? ''/?? 0 молча, Своё хранилище — по нашему домену, чужая система — по её имени: infrastructure/http/{provider}/{product}/ (G-2), Infrastructure реализует интерфейсы домена (и порт workflow), не создаёт свои контракты (+17 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Публичный метод репозитория — ровно один запрос (SELECT/UPDATE/DELETE/INSERT/upsert); count+страница пагинатора считается одним` → `Метод репозитория — одна завершающая операция (First/Find/Count/Pluck/Updates/Delete/Create/Exec); обновление — Updates + RowsAffected`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям` → `Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
- `Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic` → `internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `B-80: в счёт «4+ аргумента» не считать t *testing.T, b, f, ctx context.Context (сейчас считаются по core/style.md)` → `4+ аргумента у функции (не конструктора; ctx не в счёт) → Params в dto.go`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски»` → `Предупреждение: у entity.go/model.go нет doc-комментария на структуре или у неочевидного поля`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `Ошибка десериализации/пустое поле — errorreporter.CaptureError с ID сообщения и nil (ACK); молчаливый ACK без трекера — нет` → `Consumer handbook.parking.deactivated: нет parking_id — errorLog и return (постоянная); исключение Command — наверх (delayed 60/300/900/3600)`  [AMBIGUOUS]
  corpus/stacks/php/examples/README.md · relation: conflicts_with
- `per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)` → `per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/infra/postgres.md · relation: conflicts_with
- `Консоль — свой bootstrap internal/console/bootstrap/{domain}/{module}/; серверный bootstrap в консоль не подключается` → `Одна настройка — одно место: env-переменную читает config.go владельца, остальные — через Load{Name}() (консоль импортирует серверный пакет ради LoadMaxAttempts)`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `Права и контекст — в middleware, не в сервисе; hasRole/authorize/Gate в контроллере, сервисе, workflow — нет; чужая запись по id — 404` → `controller-auth-via-facade — [ОШИБКА]: Auth::id()/auth() в глубине сервиса вместо $request->user() в контроллере`  [AMBIGUOUS]
  corpus/stacks/php/review-tags.md · relation: conflicts_with
- `Строка таблицы без тега — [ОШИБКА] временно, до заведения тега (O-24)` → `Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with

## Knowledge Gaps
- **5 isolated node(s):** `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, контекст оркестратора, расход квоты; последовательные волны ограничивают выигрыш`, `Базовый класс ядра передаёт параметры через состояние (setQueryParams → get → prepare): сигнатура не видна и не типизируется`, `bootstrap_modules`, `project_structure`
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Публичный метод репозитория — ровно один запрос (SELECT/UPDATE/DELETE/INSERT/upsert); count+страница пагинатора считается одним` and `Метод репозитория — одна завершающая операция (First/Find/Count/Pluck/Updates/Delete/Create/Exec); обновление — Updates + RowsAffected`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям` and `Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic` and `internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `B-80: в счёт «4+ аргумента» не считать t *testing.T, b, f, ctx context.Context (сейчас считаются по core/style.md)` and `4+ аргумента у функции (не конструктора; ctx не в счёт) → Params в dto.go`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски»` and `Предупреждение: у entity.go/model.go нет doc-комментария на структуре или у неочевидного поля`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._