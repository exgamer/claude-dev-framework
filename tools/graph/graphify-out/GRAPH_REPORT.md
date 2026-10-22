# Graph Report - dev-framework rules  (2026-10-22)

## Corpus Check
- 110 files · ~116,512 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1227 nodes · 4950 edges · 9 communities detected
- Extraction: 97% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 123 edges (avg confidence: 0.82)
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
1. `stacks/php/checklist.md` - 121 edges
2. `stacks/go/checklist.md` - 116 edges
3. `approaches/process/review-tags.md` - 115 edges
4. `stacks/php/conventions.md` - 102 edges
5. `core/backlog.md` - 96 edges
6. `skills/dev/SKILL.md` - 90 edges
7. `skills/dev-php/SKILL.md` - 86 edges
8. `stacks/php/structure.md` - 81 edges
9. `skills/dev-go/SKILL.md` - 80 edges
10. `транзакции` - 79 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `Не больше 3 разработчиков одновременно (+1 QA, +1 ревьюер); refactor/migrate — до 5` --semantically_similar_to--> `Агентов аудита 4–8, а не «не больше 3 (в рефакторе 5)», как у разработчиков — потому что только чтение`  [INFERRED] [semantically similar]
  corpus/approaches/process/planning.md → corpus/approaches/process/security.md
- `Субагент не задаёт вопросы пользователю — возвращает оркестратору: вопрос, 2–3 варианта, рекомендация` --semantically_similar_to--> `Ни один пробел/спор не решён молча — строка в docs/tasks/<ключ>/rules-log.md`  [INFERRED] [semantically similar]
  corpus/approaches/process/subagent.md → corpus/approaches/process/self-check.md
- `Эндпойнт без OpenAPI/Swagger не идёт на ревью и в релиз; изменились роуты/DTO — swagger перегенерирован` --semantically_similar_to--> `Gate приёма MR: сборка/линтер/тесты локально, self-review, описание MR, Swagger обновлён, скоуп = задача, ветка/коммиты по git.md`  [INFERRED] [semantically similar]
  corpus/regulations/rest-api.md → corpus/regulations/code-review.md
- `D-3: поручения ролям — узкие адреса документов (раздел design.md; design.md >~100 КБ — по модулям), −10–15%, с A/B` --about--> `оркестратор /dev`  [EXTRACTED]
  corpus/core/backlog.md → corpus/agents/df-analyst.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Баг-фикс: процесс, оркестратор, разработчики** — file_approaches_process_bugfix_md, file_skills_dev_fix_skill_md, file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, file_approaches_process_role_chain_md [INFERRED 0.85]
- **Лимит параллельных разработчиков 3 / 5 в трёх файлах** — rule_planning_max_3_devs_5_refactor, rule_role_chain_phase4_max3_5, rule_dev_skill_max3_agents_one_tree [INFERRED 0.95]
- **не больше 3 кругов ревью** — rule_role_chain_review_max_3_rounds, rule_dev_fix_review_max_3_rounds, rule_dev_review_max_3_rounds_delta [INFERRED 0.90]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]
- **Статика первой: доказательство на эталоне, RULES.md, отказ от шумных правил** — rule_backlog_lint_proof_required, rule_dev_rule_static_first, rule_dev_rule_noisy_analyzer_rejected, rule_backlog_reviewer_static_remainder [INFERRED 0.80]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.02
Nodes (271): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, agents/df-review.md, approaches/process/analysis.md, approaches/process/bugfix.md (+263 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.02
Nodes (212): architecture/layers.md, regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/go/structure.md, stacks/php/checklist.md, stacks/php/conventions.md (+204 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (146): agents/df-security.md, approaches/patterns/cache.md, approaches/patterns/idempotency.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/checklist.md (+138 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (136): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/migrations.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md (+128 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (114): approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md (+106 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (99): architecture/integrations.md, core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/review-tags.md, stacks/go/style.md (+91 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (84): core/principles.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/domain.md (+76 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.04
Nodes (83): core/backlog.md, core/rules-evolution.md, skills/dev-rule/SKILL.md, stacks/vanilla-js/README.md, Длинные копии правила в 5–9 местах расходятся при каждом уточнении (O-23), Роль, видящая «как в <проект>», копирует чужие расхождения с правилами, B-102: тег api-docs-exposed — Swagger UI и схема только вне прода или за авторизацией, B-108: порядок (Ф) во фреймворк задаёт Шаг 3 /dev-rule — ссылаться, не перечислять decisions-log/CHANGELOG/README (+75 more)

### Community 8 - "Community 8"
Cohesion: 0.05
Nodes (82): approaches/patterns/transactions.md, architecture/cross-domain.md, skills/dev-go/SKILL.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/php/mps-core/commands.md, stacks/php/mps-core/queries.md, stacks/php/mps-core/transactions.md (+74 more)

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
- `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` → `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with

## Knowledge Gaps
- **15 isolated node(s):** `Правка по симптому без теста оставляет причину; тест, не падавший до правки, ничего не доказывает`, `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Каждый шаг агента перечитывает весь контекст: агенты со 100+ шагами (23%) — 2/3 расхода токенов`, `Почему 3: одно дерево, нарезка без пересечений, контекст оркестратора, квота (решение 2026-10-07)` (+10 more)
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
- **What is the exact relationship between `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` and `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` and `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._