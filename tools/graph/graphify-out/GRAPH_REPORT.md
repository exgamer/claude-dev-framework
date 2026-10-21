# Graph Report - dev-framework rules  (2026-10-21)

## Corpus Check
- 108 files · ~112,823 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1145 nodes · 4550 edges · 10 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 118 edges (avg confidence: 0.82)
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
1. `stacks/php/checklist.md` - 121 edges
2. `stacks/go/checklist.md` - 116 edges
3. `approaches/process/review-tags.md` - 105 edges
4. `stacks/php/conventions.md` - 102 edges
5. `core/backlog.md` - 91 edges
6. `skills/dev-review/SKILL.md` - 86 edges
7. `stacks/php/structure.md` - 82 edges
8. `транзакции` - 78 edges
9. `approaches/process/review.md` - 73 edges
10. `репозиторий` - 72 edges

## Surprising Connections (you probably didn't know these)
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `Две остановки: после документа и после плана; коммит и пуш — по отдельному «да» на каждый` --semantically_similar_to--> `Действия с БД и коммиты/пуш — только по явному «да» на каждое`  [INFERRED] [semantically similar]
  corpus/approaches/process/mr-threads.md → corpus/approaches/process/role-chain.md
- `Repository не бросает «не найдено» — ?Model, NotFoundAppException бросает вызывающий` --semantically_similar_to--> `Репозиторий не применяет бизнес-правил, не трансформирует (keyBy), не вызывает сервисы/репозитории, не бросает бизнес-исключения`  [INFERRED] [semantically similar]
  corpus/stacks/php/mps-core/exceptions.md → corpus/stacks/php/mps-core/repository.md
- `O-21: навигация — IDE (idea MCP, 4 инструмента на чтение), иначе grep; использования — всегда grep` --about--> `навигация по коду`  [EXTRACTED]
  corpus/core/decisions.md → corpus/agents/df-analyst.md
- `Навигация: IDE, если проект открыт в IDEA, иначе grep; использования и строковые связи — всегда grep; lint_files — материал, не замена правилам` --about--> `навигация по коду`  [EXTRACTED]
  corpus/skills/dev-review/SKILL.md → corpus/agents/df-analyst.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (181): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, approaches/process/analysis.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md (+173 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (153): regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/main.md, stacks/go/structure.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md (+145 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (147): agents/df-security.md, approaches/patterns/idempotency.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/checklist.md, stacks/go/go-sdk/exceptions.md (+139 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (133): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md, stacks/php/checklist.md (+125 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (132): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/self-check.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md (+124 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.04
Nodes (115): architecture/cross-domain.md, architecture/integrations.md, architecture/layers.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/go-sdk/workflow.md, stacks/php/conventions.md, stacks/php/mps-core/commands.md (+107 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.04
Nodes (106): approaches/patterns/cache.md, approaches/process/review-tags.md, core/backlog.md, core/decisions.md, core/rules-evolution.md, skills/dev-rule/SKILL.md, stacks/php/README.md, stacks/vanilla-js/README.md (+98 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.05
Nodes (81): core/style.md, stacks/go/conventions.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/review-tags.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, UI-тесты: Python + Playwright + pytest (POM) (+73 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.05
Nodes (63): core/principles.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/mps-core/capabilities.md, stacks/php/mps-core/domain.md, Справочник SDK reference/ — grep по имени, целиком не читать; нет в справочнике — нет в SDK (+55 more)

### Community 9 - "Community 9"
Cohesion: 0.12
Nodes (34): approaches/patterns/migrations.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, Колонки под фильтры — с индексом; исключение для низкоселективного фильтра внутри скоупа по индексу (дописать), Любое изменение структуры БД — раздел «Схема БД» и пункт на согласование, Миграция строго по «Схеме БД» design.md; иначе вопрос, Миграция строго по «Схеме БД» design.md; иначе вопрос, Новый NOT NULL / значение enum / смена типа — сначала выяснить данные на stage/prod; нужен default или backfill (+26 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` → `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` → `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`  [AMBIGUOUS]
  corpus/core/rules-evolution.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with

## Knowledge Gaps
- **8 isolated node(s):** `Почему без флагов: две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Почему 3: одно рабочее дерево, контекст оркестратора, расход квоты; последовательные волны ограничивают выигрыш`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения`, `Длинные копии правила в 5–9 местах расходятся при каждом уточнении (O-23)`, `Роль, видящая «как в <проект>», копирует чужие расхождения с правилами` (+3 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` and `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»` and `.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` and `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **Why does `stacks/go/checklist.md` connect `Ревью и безопасность` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Разработчик, тесты, Go-памятка`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`, `Стиль кода и текста`, `Community 9`?**
  _High betweenness centrality (0.099) - this node is a cross-community bridge._
- **Why does `stacks/php/checklist.md` connect `Разработчик, тесты, Go-памятка` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Ревью и безопасность`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`, `Стиль кода и текста`, `Community 9`?**
  _High betweenness centrality (0.079) - this node is a cross-community bridge._
- **Why does `core/backlog.md` connect `Git, MR, эволюция правил` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Ревью и безопасность`, `Разработчик, тесты, Go-памятка`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `REST, Swagger, PHP-памятка`, `Стиль кода и текста`, `Community 9`?**
  _High betweenness centrality (0.073) - this node is a cross-community bridge._