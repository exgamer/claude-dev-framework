# Graph Report - dev-framework rules  (2026-10-21)

## Corpus Check
- 108 files · ~104,693 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1112 nodes · 4475 edges · 11 communities detected
- Extraction: 95% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 192 edges (avg confidence: 0.83)
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
1. `stacks/go/checklist.md` - 111 edges
2. `stacks/php/checklist.md` - 103 edges
3. `stacks/php/conventions.md` - 97 edges
4. `approaches/process/review.md` - 93 edges
5. `skills/dev-review/SKILL.md` - 86 edges
6. `stacks/php/structure.md` - 84 edges
7. `skills/dev-php/SKILL.md` - 79 edges
8. `stacks/php/examples/README.md` - 79 edges
9. `репозиторий` - 78 edges
10. `транзакции` - 73 edges

## Surprising Connections (you probably didn't know these)
- `Инвалидация — при записи, в том же сценарии, что меняет данные` --conflicts_with--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/transactions.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции с блокировкой — только короткая работа, без внешних вызовов`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/concurrency.md
- `Каждый внешний вызов — с таймаутом; ретраи только идемпотентных и временных ошибок` --semantically_similar_to--> `Ошибки: временные — повтор, постоянные — failed и ручной разбор, без бесконечного повтора`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/outbox.md
- `У каждого нового статуса платежа есть потребитель (крон, consumer, workflow), показать в MR; тег status-without-consumer` --semantically_similar_to--> `Новый статус без потребителя (крон, consumer, переход) — status-without-consumer`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md
- `Тест на каждый платёжный сценарий — e2e по шлюзам (tests/<шлюз>/ в Go, tests/Feature/… в PHP), включая повтор колбэка и неверную подпись` --semantically_similar_to--> `Сценарий без теста при живых тестах в проекте — законное замечание; тесты не запускаются — сказать, не предлагать как проверку`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/review.md

## Hyperedges (group relationships)
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Правило транзакций O-8 в нескольких файлах** — rule_decisions_o8_transaction, rule_go_conventions_tx_atomicity, rule_go_checklist_tx_more_than_one_write, rule_go_sdk_workflow_tx_only_args, rule_review_tags_needless_tx_warning [INFERRED 0.85]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Общие шаги /dev-go и /dev-php (правишь один — проверь второй)** — file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, rule_dev_go_checklist_read_fully, rule_dev_go_local_rules_override, rule_dev_go_small_fix_without_design, rule_dev_go_threats_mandatory, rule_dev_go_no_own_sdk_replacement, rule_dev_go_format_helper_order, rule_dev_go_form_by_reference_example, rule_dev_go_db_actions_need_yes, rule_dev_go_migration_by_schema, rule_dev_go_unit_tests_with_code, rule_dev_go_lint_py_on_diff, rule_dev_go_no_commit_without_confirm, rule_dev_php_checklist_read_fully, rule_dev_php_local_rules_override, rule_dev_php_small_fix_without_design, rule_dev_php_threats_mandatory, rule_dev_php_no_own_sdk_replacement, rule_dev_php_format_helper_order, rule_dev_php_form_by_reference_example, rule_dev_php_db_actions_need_yes, rule_dev_php_migration_by_schema, rule_dev_php_unit_tests_with_code, rule_dev_php_lint_py_on_diff, rule_dev_php_no_commit_without_confirm [EXTRACTED 1.00]
- **SetTimeout/SetPerPage пагинатора описаны в 4 файлах** — rule_capabilities_paginator_timeout_seconds_perpage, rule_postgres_paginator_settimeout_seconds, rule_tariff_readme_paginator_settimeout_seconds, rule_review_tags_paginator_timeout_units_error [INFERRED 0.90]
- **Транзакции PHP через TransactionManagerInterface** — rule_review_checks_tx_via_manager_interface, rule_review_checks_single_crud_no_tx, rule_structure_tx_manager_core_version [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.02
Nodes (201): approaches/patterns/transactions.md, architecture/cross-domain.md, architecture/integrations.md, architecture/layers.md, stacks/go/conventions.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/http.md (+193 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (173): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, core/decisions.md, core/rules-evolution.md, regulations/code-review.md, regulations/git.md (+165 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (126): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/planning.md (+118 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (122): regulations/entity.md, regulations/rest-api.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/entrypoint/http.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md (+114 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (108): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/php/examples/README.md, stacks/php/mps-core/cli.md (+100 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.06
Nodes (76): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/exceptions.md, stacks/go/review-checks.md, stacks/go/security.md, stacks/php/security.md (+68 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.06
Nodes (68): approaches/patterns/cache.md, regulations/service-requirements.md, stacks/go/go-sdk/infra/redis.md, stacks/go/review-findings.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/rules.md, stacks/php/review-checks.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены (+60 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (68): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, approaches/process/testing.md, regulations/contracts.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах (+60 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (61): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, B-67: кандидаты графа — style.md против Go п. 13 (doc на сущности), Go п. 23a против async.md, capabilities.md против conventions.md, layers.md против cross-domain.md, Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски», Ресиверы — одна буква; аббревиатуры заглавными ID/URL/DTO, Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение (+53 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.07
Nodes (56): approaches/process/self-check.md, core/principles.md, skills/dev-go/SKILL.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/main.md, stacks/php/checklist.md, Общее для нескольких сервисов, чего нет в SDK — вопрос «добавить в SDK?» пользователю, не универсальный пакет молча (+48 more)

### Community 10 - "Community 10"
Cohesion: 0.08
Nodes (53): approaches/patterns/migrations.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, SetTimeout(n) — n секунд (10*time.Second переполнение), без вызова 10 с; SetPerPage только при n>0 (умолчание 30, 0 даёт 20), Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Миграция строго по «Схеме БД» design.md; другое поле/таблица — вопрос, не правка, Миграция строго по «Схеме БД» design.md; другое поле/таблица — вопрос, не правка (+45 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` → `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`  [AMBIGUOUS]
  corpus/core/principles.md · relation: conflicts_with
- `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка rules-log.md` → `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`  [AMBIGUOUS]
  corpus/stacks/php/review-checks.md · relation: conflicts_with
- `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` → `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `Common → ни от чего, кроме языка и библиотек дат` → `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`  [AMBIGUOUS]
  corpus/architecture/layers.md · relation: conflicts_with
- `DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип` → `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`  [AMBIGUOUS]
  corpus/regulations/contracts.md · relation: conflicts_with
- `B-57: oneof=KZT RUB USD в теге — дубль enum (enum-list-drift); нужно своё правило через CustomValidationRules()` → `Границы: page lte=10000, per_page lte=100; невалидный id пути — 422; правила в теге binding (validate SDK не читает)`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `B-67: кандидаты графа — style.md против Go п. 13 (doc на сущности), Go п. 23a против async.md, capabilities.md против conventions.md, layers.md против cross-domain.md` → `Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски»`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `B-67: кандидаты графа — style.md против Go п. 13 (doc на сущности), Go п. 23a против async.md, capabilities.md против conventions.md, layers.md против cross-domain.md` → `Consumer тонкий и идемпотентный; ошибка десериализации — ACK, временная — NACK; топология при старте через SDK`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)` → `Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Источник SDK и версии из go.mod записываются в раздел «SDK» design.md (субагент — в итог оркестратору); без «SDK» код на SDK не начинать` → `Версия ядра — из composer.lock; нет справочника версии — собрать tools/sdk-ref/php.sh; запись версии в design.md не требуется`  [AMBIGUOUS]
  corpus/skills/dev-go/SKILL.md · relation: conflicts_with
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

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]` and `Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База ревью MR — Target Branch этого MR; без glab и без названной цели — stage` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка rules-log.md` and `Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица` and `Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Common → ни от чего, кроме языка и библиотек дат` and `Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип` and `Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._