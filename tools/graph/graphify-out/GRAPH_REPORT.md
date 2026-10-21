# Graph Report - dev-framework rules  (2026-10-22)

## Corpus Check
- 110 files · ~115,380 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1206 nodes · 4895 edges · 12 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 158 edges (avg confidence: 0.82)
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
1. `stacks/php/checklist.md` - 120 edges
2. `stacks/go/checklist.md` - 116 edges
3. `approaches/process/review-tags.md` - 102 edges
4. `stacks/php/conventions.md` - 102 edges
5. `skills/dev-php/SKILL.md` - 81 edges
6. `stacks/php/structure.md` - 81 edges
7. `транзакции` - 78 edges
8. `репозиторий` - 75 edges
9. `skills/dev-go/SKILL.md` - 74 edges
10. `core/principles.md` - 73 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции с блокировкой — только короткая работа, без внешних вызовов`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/concurrency.md
- `Не баг-фикс (стоп, /dev): миграция/схема БД, изменение контракта, новые права/роли/middleware, другой сервис/SDK` --semantically_similar_to--> `Баг-фикс /dev-fix в пределах контракта — без design даже в чувствительной зоне; новые права/роли/middleware — /dev с design`  [INFERRED] [semantically similar]
  corpus/approaches/process/bugfix.md → corpus/approaches/process/security.md
- `Вернуть подразумеваемую контрактом проверку (broken-access-control, tenant-scope-missing) — баг-фикс; новое правило видимости — контракт` --semantically_similar_to--> `Баг-фикс /dev-fix в пределах контракта — без design даже в чувствительной зоне; новые права/роли/middleware — /dev с design`  [INFERRED] [semantically similar]
  corpus/approaches/process/bugfix.md → corpus/approaches/process/security.md
- `Файл целиком — только ради реализации; иначе индекс IDE и Read offset/limit; в итоге — число прочитанных целиком файлов` --semantically_similar_to--> `Справочник reference/ — grep по имени, файл целиком не читать`  [INFERRED] [semantically similar]
  corpus/approaches/process/code-navigation.md → corpus/skills/dev-go/SKILL.md
- `Уровни: unit — разработчик без БД/HTTP/очереди; интеграционный и e2e — QA в волне 4, до ревью` --semantically_similar_to--> `Волны: 0 контракты, 1–2 реализации, 3 регистрация (один исполнитель), 4 QA, 5 ревью ⇄ исправления до 3 кругов`  [INFERRED] [semantically similar]
  corpus/approaches/process/testing.md → corpus/approaches/process/planning.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Баг-фикс: порядок, роль, тесты, безопасность** — file_approaches_process_bugfix_md, file_skills_dev_fix_skill_md, file_approaches_process_testing_md, file_approaches_process_security_md, file_approaches_process_role_chain_md [EXTRACTED 1.00]
- **Лимиты параллельных агентов** — rule_planning_max_3_devs_5_refactor, rule_role_chain_parallel_3_refactor_5, rule_security_audit_agents_vs_dev_limit, rule_role_chain_no_agent_tool [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (222): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-review.md, approaches/process/analysis.md, approaches/process/bugfix.md, approaches/process/code-navigation.md (+214 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (153): approaches/patterns/transactions.md, architecture/cross-domain.md, architecture/layers.md, stacks/go/checklist.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/postgres.md, stacks/go/go-sdk/workflow.md, stacks/go/structure.md (+145 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (141): regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/php/checklist.md, stacks/php/conventions.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md (+133 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (115): agents/df-security.md, approaches/patterns/idempotency.md, approaches/patterns/payments.md, approaches/process/security.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/infra/redis.md, stacks/go/review-findings.md, stacks/go/security.md (+107 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.05
Nodes (94): approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md (+86 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (91): architecture/integrations.md, core/style.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md (+83 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.04
Nodes (81): regulations/service-requirements.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/exceptions.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/domain.md, stacks/php/mps-core/exceptions.md (+73 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (81): approaches/process/self-check.md, core/principles.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/capabilities.md, stacks/php/mps-core/capabilities.md, stacks/php/README.md, Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения (+73 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (73): approaches/patterns/cache.md, approaches/patterns/migrations.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, B-91: «колонки под фильтры — с индексом» без исключения для низкоселективных фильтров, Cache-aside — inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе; «резолверы» с фоновым обновлением отклонены, Инвалидация — при записи, в том же сценарии, что меняет данные (+65 more)

### Community 9 - "Community 9"
Cohesion: 0.06
Nodes (69): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/outbox.md, stacks/go/examples/tariff/README.md, stacks/php/examples/README.md, stacks/php/mps-core/cli.md, stacks/php/mps-core/jobs.md, Асинхронность решает вызывающий (workflow/entrypoint/runner), не сервис; скрытая горутина/dispatch — hidden-async-in-service (+61 more)

### Community 10 - "Community 10"
Cohesion: 0.09
Nodes (47): agents/df-qa.md, approaches/process/testing.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, D-9: workflow.md разрешает интерфейс на сервис домена для подмены в тесте, contracts.md — «в Go бизнес-сервисы не мокаем»; рекомендация — конкретный тип, Имя теста бага — нарушенное правило (TestSetDefault_ActiveTariffBecomesDefault), не номер бага, O-9: массовые операции, очистка, схема/миграции — только по явному «да» на каждую; stage/prod — никогда, refactor/migrate: характеризующие тесты — первая задача волны 0 (+39 more)

### Community 11 - "Community 11"
Cohesion: 0.09
Nodes (39): core/backlog.md, core/rules-evolution.md, skills/dev-rule/SKILL.md, stacks/vanilla-js/README.md, Длинные копии правила в 5–9 местах расходятся при каждом уточнении (O-23), Роль, видящая «как в <проект>», копирует чужие расхождения с правилами, B-85: интеграционные прогоны на общей тестовой БД — только по очереди, B-93: новое поле модели в кеше — новая версия ключа или сброс при выкатке (+31 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Граница баг-фикса: больше одного модуля или ~больше 10 файлов без тестов → /dev` → `Предел задачи: один модуль одного домена, ≤2 соседних слоя, ориентир 8–10 файлов без тестов`  [AMBIGUOUS]
  corpus/approaches/process/bugfix.md · relation: conflicts_with
- `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` → `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` → `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Ревью df-review по git diff release; [ОШИБКА]/[БЕЗОПАСНОСТЬ] — свежим агентом; повторный круг по дельте; не больше 3 кругов` → `[ВНИМАНИЕ] с пометкой «унаследовано» попутно не исправляются — в отчёт и rules-log (review.md п. 3a)`  [AMBIGUOUS]
  corpus/skills/dev/SKILL.md · relation: conflicts_with

## Knowledge Gaps
- **12 isolated node(s):** `Правка по симптому без теста оставляет причину; тест, не падавший до правки, ничего не доказывает`, `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, нарезка без пересечений, контекст оркестратора, расход квоты (решение 2026-10-07)`, `Продолженный агент тянет весь прошлый контекст: 150–400k токенов на шаг против ~35k у свежего` (+7 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Граница баг-фикса: больше одного модуля или ~больше 10 файлов без тестов → /dev` and `Предел задачи: один модуль одного домена, ≤2 соседних слоя, ориентир 8–10 файлов без тестов`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` and `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` and `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` and `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Ревью df-review по git diff release; [ОШИБКА]/[БЕЗОПАСНОСТЬ] — свежим агентом; повторный круг по дельте; не больше 3 кругов` and `[ВНИМАНИЕ] с пометкой «унаследовано» попутно не исправляются — в отчёт и rules-log (review.md п. 3a)`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **Why does `stacks/php/checklist.md` connect `Ревью и безопасность` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Разработчик, тесты, Go-памятка`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`, `Стиль кода и текста`, `Community 9`, `Community 10`?**
  _High betweenness centrality (0.077) - this node is a cross-community bridge._