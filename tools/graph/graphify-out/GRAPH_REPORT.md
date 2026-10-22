# Graph Report - dev-framework rules  (2026-10-22)

## Corpus Check
- 110 files · ~118,585 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1231 nodes · 4964 edges · 9 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 128 edges (avg confidence: 0.83)
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

## God Nodes (most connected - your core abstractions)
1. `stacks/php/conventions.md` - 102 edges
2. `approaches/process/review-tags.md` - 98 edges
3. `stacks/php/checklist.md` - 97 edges
4. `stacks/go/checklist.md` - 92 edges
5. `skills/dev/SKILL.md` - 90 edges
6. `skills/dev-php/SKILL.md` - 86 edges
7. `core/backlog.md` - 85 edges
8. `транзакции` - 82 edges
9. `stacks/php/structure.md` - 81 edges
10. `skills/dev-go/SKILL.md` - 80 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции с блокировкой — только короткая работа, без внешних вызовов`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/concurrency.md
- `Субагент не задаёт вопросы пользователю — возвращает оркестратору: вопрос, 2–3 варианта, рекомендация` --semantically_similar_to--> `Ни один пробел/спор не решён молча — строка в docs/tasks/<ключ>/rules-log.md`  [INFERRED] [semantically similar]
  corpus/approaches/process/subagent.md → corpus/approaches/process/self-check.md
- `Repository не бросает «не найдено» — ?Model, NotFoundAppException бросает вызывающий` --semantically_similar_to--> `Репозиторий не применяет бизнес-правил, не трансформирует (keyBy), не вызывает сервисы/репозитории, не бросает бизнес-исключения`  [INFERRED] [semantically similar]
  corpus/stacks/php/mps-core/exceptions.md → corpus/stacks/php/mps-core/repository.md
- `В чат — только итог, в цепочке /dev не длиннее 15 строк (subagent.md)` --about--> `субагенты`  [EXTRACTED]
  corpus/approaches/process/review.md → corpus/agents/df-analyst.md
- `Аналитик-субагент пользователя не спрашивает: неясности — в «Неясности» requirements.md и в итог` --semantically_similar_to--> `Архитектор-субагент не выполняет Шаг 5 (вопросы пользователю): вопросы — в «Открытые вопросы» design.md и в итог`  [INFERRED] [semantically similar]
  corpus/agents/df-analyst.md → corpus/agents/df-architect.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Баг-фикс: процесс, оркестратор, разработчики** — file_approaches_process_bugfix_md, file_skills_dev_fix_skill_md, file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, file_approaches_process_role_chain_md [INFERRED 0.85]
- **Лимит параллельных разработчиков 3 / 5 в трёх файлах** — rule_planning_max_3_devs_5_refactor, rule_role_chain_phase4_max3_5, rule_dev_skill_max3_agents_one_tree [INFERRED 0.95]
- **Умолчание производного фильтра: default в OpenAPI или текстом** — rule_approaches_process_review_tags_filters_default_in_openapi, rule_regulations_rest_api_filters_default_as_text, rule_stacks_go_checklist_filters_default_text, rule_stacks_php_checklist_filters_default_text [INFERRED 0.85]
- **не больше 3 кругов ревью** — rule_role_chain_review_max_3_rounds, rule_dev_fix_review_max_3_rounds, rule_dev_review_max_3_rounds_delta [INFERRED 0.90]
- **Безопасность: каталог тегов + признаки по стекам + памятки** — file_approaches_process_security_md, file_stacks_go_security_md, file_stacks_php_security_md, file_stacks_go_checklist_md, file_stacks_php_checklist_md [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Цикл правки фреймворка: решение → журнал → проверка → бэклог** — file_core_rules_evolution_md, file_skills_dev_rule_skill_md, file_core_backlog_md, topic_rules_evolution [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (246): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, agents/df-review.md, approaches/process/analysis.md, approaches/process/bugfix.md (+238 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (174): architecture/integrations.md, core/principles.md, regulations/service-requirements.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/entrypoint/http.md (+166 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (160): agents/df-security.md, approaches/patterns/payments.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/security.md, regulations/code-review.md, skills/dev-review/SKILL.md, skills/dev-security/SKILL.md (+152 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (139): approaches/patterns/transactions.md, architecture/cross-domain.md, architecture/layers.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/php/mps-core/commands.md, stacks/php/mps-core/queries.md, stacks/php/mps-core/repository.md (+131 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (135): regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/main.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md, stacks/php/mps-core/model.md (+127 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.03
Nodes (117): approaches/process/self-check.md, core/style.md, regulations/git.md, stacks/go/checklist.md, stacks/php/checklist.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения (+109 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.04
Nodes (102): approaches/patterns/cache.md, approaches/patterns/migrations.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/migrations.md (+94 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (82): approaches/process/mr-threads.md, core/rules-evolution.md, skills/dev-mr/SKILL.md, skills/dev-rule/SKILL.md, stacks/php/README.md, stacks/vanilla-js/README.md, design-not-matching-code — [ИНФО]: код расходится с design.md без строки в rules-log.md, Строка бэклога: 6 колонок (ID, уровень, где, суть, что сделать, источник); ID B-N «Исправить» / D-N «Решить» (+74 more)

### Community 8 - "Community 8"
Cohesion: 0.05
Nodes (76): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, stacks/php/examples/README.md, stacks/php/mps-core/cli.md, stacks/php/mps-core/jobs.md, side-effect-without-outbox [ОШИБКА]: побочный эффект с гарантией доставки (платёжный путь — всегда) без outbox (+68 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Не баг-фикс → стоп и /dev: миграция/схема БД, изменение контракта или модели доступа, >1 модуля или ~>10 файлов без тестов, правка в другом сервисе/SDK` → `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Чувствительная зона — не граница баг-фикса: самопроверка по stacks/<stack>/security.md, модель угроз не нужна` → `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Тот же разработчик (SendMessage) только после «ок» на причину в --step и при одном возврате к п.2; исправления по ревью — свежий агент` → `В /dev-fix координатор код не пишет: разработка и ревью — свежие агенты df-go/df-php и df-review`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` → `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `(Ф) во фреймворк — правка в источнике + строка в core/decisions-log.md (CHANGELOG/README не названы)` → `Решение — строка в decisions-log.md + пункт CHANGELOG.md под датой; README.md — при смене установки/команд`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with

## Knowledge Gaps
- **13 isolated node(s):** `Правка по симптому без теста оставляет причину; тест, не падавший до правки, ничего не доказывает`, `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Каждый шаг агента перечитывает весь контекст: агенты со 100+ шагами (23%) — 2/3 расхода токенов`, `Почему 3: одно дерево, нарезка без пересечений, контекст оркестратора, квота (решение 2026-10-07)` (+8 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Не баг-фикс → стоп и /dev: миграция/схема БД, изменение контракта или модели доступа, >1 модуля или ~>10 файлов без тестов, правка в другом сервисе/SDK` and `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Чувствительная зона — не граница баг-фикса: самопроверка по stacks/<stack>/security.md, модель угроз не нужна` and `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Тот же разработчик (SendMessage) только после «ок» на причину в --step и при одном возврате к п.2; исправления по ревью — свежий агент` and `В /dev-fix координатор код не пишет: разработка и ревью — свежие агенты df-go/df-php и df-review`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` and `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `(Ф) во фреймворк — правка в источнике + строка в core/decisions-log.md (CHANGELOG/README не названы)` and `Решение — строка в decisions-log.md + пункт CHANGELOG.md под датой; README.md — при смене установки/команд`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` and `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._