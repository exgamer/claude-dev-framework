# Graph Report - dev-framework rules  (2026-10-21)

## Corpus Check
- 108 files · ~107,227 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1128 nodes · 4524 edges · 10 communities detected
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 164 edges (avg confidence: 0.82)
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
1. `stacks/php/conventions.md` - 126 edges
2. `stacks/php/checklist.md` - 100 edges
3. `approaches/process/review.md` - 93 edges
4. `репозиторий` - 92 edges
5. `skills/dev-review/SKILL.md` - 86 edges
6. `stacks/go/checklist.md` - 86 edges
7. `stacks/php/structure.md` - 84 edges
8. `транзакции` - 82 edges
9. `approaches/process/review-tags.md` - 81 edges
10. `architecture/layers.md` - 80 edges

## Surprising Connections (you probably didn't know these)
- `Нельзя: отправлять наружу внутри транзакции; своё соединение с брокером в обход ядра (amqp.Dial, MR!151)` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/outbox.md → corpus/approaches/patterns/transactions.md
- `У каждого нового статуса платежа есть потребитель (крон, consumer, workflow), показать в MR; тег status-without-consumer` --semantically_similar_to--> `Новый статус без потребителя (крон, consumer, переход) — status-without-consumer`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Тест на каждый платёжный сценарий — e2e по шлюзам (tests/<шлюз>/ в Go, tests/Feature/… в PHP), включая повтор колбэка и неверную подпись` --semantically_similar_to--> `Сценарий без теста при живых тестах в проекте — законное замечание; тесты не запускаются — сказать, не предлагать как проверку`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `O-8: транзакция только при >1 модифицирующем запросе; один запрос — без транзакции (кроме FOR UPDATE); внешние вызовы вне транзакции` --semantically_similar_to--> `multi-write-without-transaction — [ОШИБКА]; needless-transaction (один запрос без блокировки) — [ВНИМАНИЕ]`  [INFERRED] [semantically similar]
  corpus/core/decisions.md → corpus/approaches/process/review-tags.md

## Hyperedges (group relationships)
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Скоуп без умолчания «без ограничения» в 4 местах** — rule_layers_scope_no_unrestricted_default, rule_go_conventions_p7a_parking_scope_type, rule_php_checklist_p29_scope_no_default_null, rule_go_checklist_p28_scope_every_handler [INFERRED 0.85]
- **Правило O-8 «транзакция только при >1 записи» в 5 местах** — rule_decisions_o8_tx_only_justified, rule_go_conventions_p17_tx_more_than_one_write, rule_go_checklist_p14_tx_more_than_one, rule_php_checklist_p19_tx_more_than_one_run, rule_go_sdk_domain_command_tx_more_than_one [INFERRED 0.85]
- **Переход состояния: своя Command + условный UPDATE + метод модели** — rule_entity_transition_own_command, rule_entity_conditional_update_422, rule_go_checklist_put_patch_no_status, rule_go_tariff_readme_set_default_own_endpoint, rule_php_examples_readme_state_not_fillable [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (206): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md (+198 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (167): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/self-check.md, core/backlog.md, core/rules-evolution.md, regulations/code-review.md (+159 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (166): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/checklist.md, stacks/go/examples/tariff/README.md (+158 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (131): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/exceptions.md, stacks/go/review-checks.md, stacks/go/review-findings.md (+123 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (118): architecture/cross-domain.md, architecture/layers.md, regulations/entity.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/go/structure.md, stacks/php/mps-core/model.md, stacks/php/mps-core/repository.md (+110 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (104): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/php/checklist.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md, stacks/php/mps-core/swagger.md (+96 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.04
Nodes (97): architecture/integrations.md, core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md (+89 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (78): approaches/patterns/cache.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/infra/redis.md, stacks/go/go-sdk/main.md, stacks/php/mps-core/capabilities.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены, Инвалидация — при записи, в том же сценарии, что меняет данные (+70 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.12
Nodes (36): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Миграция строго по «Схеме БД» design.md; иначе вопрос, Миграция строго по «Схеме БД» design.md; иначе вопрос, Миграции, сиды, массовые правки — не запускать без явного «да» на каждую, Миграция строго по согласованной «Схеме БД» design.md; обратно совместима (expand/contract), Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill (+28 more)

### Community 9 - "Community 9"
Cohesion: 0.12
Nodes (25): stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/domain.md, DI (Register/Resolve, MustResolve только на старте) — только в app/bootstrap, Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface, Зависимости через конструктор private readonly, тип — интерфейс; app()/resolve() в бизнес-коде запрещены (кроме миграций и Artisan::command), DI только через definitions.php (di-outside-definitions); $this->app->bind() — только для подпроектных портов с переопределением извне, a.Container — только внутри App (bootstrap, модули), никогда в бизнес-логику и репозитории (+17 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `DI только в сборке; резолв в бизнес-коде — di-resolve-in-business-code; Go — сервисы конкретным типом, PHP — ServiceInterface всегда` → `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`  [AMBIGUOUS]
  corpus/regulations/contracts.md · relation: conflicts_with
- `Common — только enum, value-объекты, чистые функции; без DI и I/O (кроме языка, дат и трейта enum ядра); копия — duplicated-enum` → `Common → ни от чего, кроме языка и библиотек дат (схема зависимостей)`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда` → `Предупреждение: доменная сущность/модель без doc-комментария на структуре — предупреждение`  [AMBIGUOUS]
  corpus/stacks/go/conventions.md · relation: conflicts_with
- `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда` → `п.37: модель — @property по-русски (О), $table со схемой, $fillable, $casts`  [AMBIGUOUS]
  corpus/stacks/php/checklist.md · relation: conflicts_with
- `Массовый переход — один UPDATE без загрузки сущностей, условие то же, что CanArchive()/canArchive() модели, своя Command` → `Переход «по умолчанию» — POST /tariffs/:id/set-default, своя Command; 0 строк → ErrTariffStateChanged → 422`  [AMBIGUOUS]
  corpus/regulations/entity.md · relation: conflicts_with
- `Doc-комментарий на публичных типах — только если добавляет смысл к имени, формат «Имя Описание по-русски»` → `Docblock класса и @property на каждое поле модели обязательны; описание — у неочевидных`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `п.7a: скоуп — ParkingScope{All bool; IDs []uint}; nil []uint = «все» запрещён; 2+ домена — в internal/common/` → `Пример Repository.GetByID(ctx, id) — без аргумента скоупа`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/domain.md · relation: conflicts_with
- `Путь /healthcheck/* — вне схемы /api/<context>/<version>/… намеренно; сбой — 503 без Sentry` → `Healthcheck — готовый MPS\Utils HealthCheck (/health/liveness, /health/readiness), своего нет`  [AMBIGUOUS]
  corpus/stacks/go/examples/tariff/README.md · relation: conflicts_with
- `per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)` → `per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/infra/postgres.md · relation: conflicts_with
- `Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true` → `В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `Права и контекст — в middleware, не в сервисе; hasRole/authorize/Gate в контроллере, сервисе, workflow — нет; чужая запись по id — 404` → `controller-auth-via-facade — [ОШИБКА]: Auth::id()/auth() в глубине сервиса вместо $request->user() в контроллере`  [AMBIGUOUS]
  corpus/stacks/php/review-tags.md · relation: conflicts_with
- `Строка таблицы без тега — [ОШИБКА] временно, до заведения тега (O-24)` → `Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Бизнес-логика и бизнес-исключения в Model/Repository — нарушение; репозиторий возвращает null, «не найдено» решает вызывающий` → `Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории`  [AMBIGUOUS]
  corpus/stacks/php/structure.md · relation: conflicts_with

## Knowledge Gaps
- **5 isolated node(s):** `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, контекст оркестратора, расход квоты; последовательные волны ограничивают выигрыш`, `Базовый класс ядра передаёт параметры через состояние (setQueryParams → get → prepare): сигнатура не видна и не типизируется`, `context_propagation`, `code_form`
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `DI только в сборке; резолв в бизнес-коде — di-resolve-in-business-code; Go — сервисы конкретным типом, PHP — ServiceInterface всегда` and `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Common — только enum, value-объекты, чистые функции; без DI и I/O (кроме языка, дат и трейта enum ядра); копия — duplicated-enum` and `Common → ни от чего, кроме языка и библиотек дат (схема зависимостей)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда` and `Предупреждение: доменная сущность/модель без doc-комментария на структуре — предупреждение`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда` and `п.37: модель — @property по-русски (О), $table со схемой, $fillable, $casts`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Массовый переход — один UPDATE без загрузки сущностей, условие то же, что CanArchive()/canArchive() модели, своя Command` and `Переход «по умолчанию» — POST /tariffs/:id/set-default, своя Command; 0 строк → ErrTariffStateChanged → 422`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._