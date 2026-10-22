# Graph Report - dev-framework rules  (2026-10-22)

## Corpus Check
- 110 files · ~115,655 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1195 nodes · 4882 edges · 11 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 135 edges (avg confidence: 0.82)
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

## God Nodes (most connected - your core abstractions)
1. `stacks/php/checklist.md` - 120 edges
2. `stacks/go/checklist.md` - 116 edges
3. `approaches/process/review-tags.md` - 115 edges
4. `stacks/php/conventions.md` - 102 edges
5. `skills/dev-php/SKILL.md` - 86 edges
6. `stacks/php/structure.md` - 81 edges
7. `skills/dev-go/SKILL.md` - 80 edges
8. `транзакции` - 79 edges
9. `репозиторий` - 78 edges
10. `core/principles.md` - 74 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `Уровни: unit — разработчик без БД/HTTP/очереди; интеграционный и e2e — QA в волне 4, до ревью` --semantically_similar_to--> `Волны: 0 контракты, 1–2 реализации, 3 регистрация (один исполнитель), 4 QA, 5 ревью ⇄ исправления до 3 кругов`  [INFERRED] [semantically similar]
  corpus/approaches/process/testing.md → corpus/approaches/process/planning.md
- `Не больше 3 разработчиков одновременно (+1 QA, +1 ревьюер); в refactor/migrate — до 5` --semantically_similar_to--> `Агентов аудита 4–8, а не «не больше 3 (в рефакторе 5)», как у разработчиков — потому что только чтение`  [INFERRED] [semantically similar]
  corpus/approaches/process/planning.md → corpus/approaches/process/security.md
- `Конкурентные места — интеграционный тест с параллельными запросами на реальной БД; unit ничего не доказывает` --semantically_similar_to--> `DoD: независимое ревью ПРОЙДЕНО — 0 [ОШИБКА], 0 [БЕЗОПАСНОСТЬ]; go test -race чистый; govulncheck/composer audit`  [INFERRED] [semantically similar]
  corpus/approaches/process/testing.md → corpus/approaches/process/role-chain.md
- `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк` --conflicts_with--> `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»`  [AMBIGUOUS]
  corpus/core/rules-evolution.md → corpus/core/principles.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Баг-фикс: процесс, оркестратор, разработчики** — file_approaches_process_bugfix_md, file_skills_dev_fix_skill_md, file_skills_dev_go_skill_md, file_skills_dev_php_skill_md, file_approaches_process_role_chain_md [INFERRED 0.85]
- **Лимиты параллельных агентов** — rule_planning_max_3_devs_5_refactor, rule_role_chain_parallel_3_refactor_5, rule_security_audit_agents_vs_dev_limit, rule_role_chain_no_agent_tool [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (192): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-review.md, approaches/process/analysis.md, approaches/process/bugfix.md, approaches/process/code-navigation.md (+184 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (159): architecture/cross-domain.md, architecture/layers.md, core/backlog.md, core/decisions.md, regulations/entity.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/workflow.md, stacks/go/review-checks.md (+151 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (156): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/checklist.md, stacks/go/examples/tariff/README.md (+148 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.03
Nodes (147): approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/self-check.md, core/rules-evolution.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md (+139 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (126): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/exceptions.md, stacks/go/review-findings.md, stacks/go/security.md (+118 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (116): regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/php/checklist.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/http.md, stacks/php/mps-core/swagger.md (+108 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.04
Nodes (96): approaches/process/review-tags.md, architecture/integrations.md, core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/infra/http.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md (+88 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (77): agents/df-qa.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, approaches/process/testing.md, regulations/contracts.md, skills/dev-qa/SKILL.md, stacks/go/structure.md, stacks/php/mps-core/tests.md (+69 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (60): approaches/patterns/cache.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/capabilities.md, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены, Инвалидация — при записи, в том же сценарии, что меняет данные, Ключ — {service}:{entity}:{id}; префикс сервиса обязателен (общий Redis), все параметры — в ключе, Промах кэша — (nil, nil); ошибка Redis не роняет сценарий: CaptureSoft, идём в БД, Секреты (ключи, JWT-секреты, креды) в Redis не кладутся (+52 more)

### Community 9 - "Community 9"
Cohesion: 0.12
Nodes (35): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Миграция — строго по «Схеме БД» design.md; другое поле/таблица — вопрос, Миграция — строго по «Схеме БД» design.md; другое поле/таблица — вопрос, Задача с миграцией — только при «да» на «Схему БД» в answers.md, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill (+27 more)

### Community 10 - "Community 10"
Cohesion: 0.09
Nodes (31): stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/domain.md, a.Container — только внутри App (bootstrap, модули), никогда в бизнес-логику и репозитории, Зависимости kernel — через хелперы (GetDefaultPostgresConnection, GetRedisClient…), не di.Resolve, nil вместо обязательной зависимости в конструктор не передаётся (nil-dependency-injected), Таблица путей ревью из go-reviewer; при расхождении главнее structure.md (G-2, G-4) (+23 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Не баг-фикс → стоп и /dev: миграция/схема БД, изменение контракта или модели доступа, >1 модуля или ~>10 файлов без тестов, правка в другом сервисе/SDK` → `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Чувствительная зона — не граница баг-фикса: самопроверка по stacks/<stack>/security.md, модель угроз не нужна` → `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Тот же разработчик (SendMessage) только после «ок» на причину в --step и при одном возврате к п.2; исправления по ревью — свежий агент` → `/dev-fix — координатор как /dev: код не пишет, разработка и ревью — свежие df-go/df-php и df-review`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` → `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `D-9: workflow.md разрешает интерфейс на сервис домена для подмены в тесте, contracts.md — «в Go бизнес-сервисы не мокаем»; рекомендация — конкретный тип` → `Форма — по эталону stacks/go/examples; сосед/легаси при расхождении — не образец`  [AMBIGUOUS]
  corpus/core/backlog.md · relation: conflicts_with
- `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` → `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Ревью df-review по git diff release; [ОШИБКА]/[БЕЗОПАСНОСТЬ] — свежим агентом; повторный круг по дельте; не больше 3 кругов` → `[ВНИМАНИЕ] с пометкой «унаследовано» попутно не исправляются — в отчёт и rules-log (review.md п. 3a)`  [AMBIGUOUS]
  corpus/skills/dev/SKILL.md · relation: conflicts_with

## Knowledge Gaps
- **13 isolated node(s):** `Правка по симптому без теста оставляет причину; тест, не падавший до правки, ничего не доказывает`, `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, нарезка без пересечений, контекст оркестратора, расход квоты (решение 2026-10-07)`, `Продолженный агент тянет весь прошлый контекст: 150–400k токенов на шаг против ~35k у свежего` (+8 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Не баг-фикс → стоп и /dev: миграция/схема БД, изменение контракта или модели доступа, >1 модуля или ~>10 файлов без тестов, правка в другом сервисе/SDK` and `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Чувствительная зона — не граница баг-фикса: самопроверка по stacks/<stack>/security.md, модель угроз не нужна` and `Без design.md — только мелкая правка: один модуль, без эндпойнтов, таблиц/миграций, событий, контракта и чувствительных зон; иначе /dev`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Тот же разработчик (SendMessage) только после «ок» на причину в --step и при одном возврате к п.2; исправления по ревью — свежий агент` and `/dev-fix — координатор как /dev: код не пишет, разработка и ревью — свежие df-go/df-php и df-review`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` and `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `D-9: workflow.md разрешает интерфейс на сервис домена для подмены в тесте, contracts.md — «в Go бизнес-сервисы не мокаем»; рекомендация — конкретный тип` and `Форма — по эталону stacks/go/examples; сосед/легаси при расхождении — не образец`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` and `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._