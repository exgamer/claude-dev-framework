# Graph Report - dev-framework rules  (2026-10-20)

## Corpus Check
- 106 files · ~77,516 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1147 nodes · 4500 edges · 10 communities detected
- Extraction: 95% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 200 edges (avg confidence: 0.82)
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

## God Nodes (most connected - your core abstractions)
1. `Репозиторий` - 103 edges
2. `stacks/php/checklist.md` - 98 edges
3. `stacks/php/conventions.md` - 96 edges
4. `stacks/go/checklist.md` - 95 edges
5. `Транзакции` - 82 edges
6. `stacks/php/structure.md` - 78 edges
7. `skills/dev-architect/SKILL.md` - 76 edges
8. `Слои` - 75 edges
9. `approaches/process/role-chain.md` - 75 edges
10. `skills/dev/SKILL.md` - 74 edges

## Surprising Connections (you probably didn't know these)
- `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` --conflicts_with--> `Ревью ⇄ исправления — до 3 кругов`  [AMBIGUOUS]
  corpus/agents/df-review.md → corpus/approaches/process/planning.md
- `Go: горутины только с ограничением параллелизма и отменой по ctx` --semantically_similar_to--> `Go: горутина с явным lifecycle, контекст от app, не context.Background()`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/async.md
- `Сценарии включают «одновременно» и «повтор»` --semantically_similar_to--> `Архитектор отмечает раздел «Конкурентность» в design.md; нет одновременности — одна строка`  [INFERRED] [semantically similar]
  corpus/approaches/process/analysis.md → corpus/approaches/patterns/concurrency.md
- `У каждого нового статуса платежа есть потребитель (status-without-consumer)` --semantically_similar_to--> `Новый статус без потребителя — [ОШИБКА] status-without-consumer`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/payments.md → corpus/approaches/process/impact-analysis.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md

## Hyperedges (group relationships)
- **Побочные эффекты только после коммита / через outbox** — rule_transactions_no_side_effects_in_tx, rule_outbox_no_send_in_tx, rule_performance_short_transaction, rule_concurrency_no_external_in_lock_tx, rule_payments_side_effects_outbox [INFERRED 0.85]
- **Статус без потребителя — тупик** — rule_payments_status_has_consumer, rule_impact_analysis_status_without_consumer, rule_idempotency_final_status_not_overwritten [INFERRED 0.80]
- **Правило «метод репозитория — один запрос» в общем и стековых файлах** — rule_layers_repo_one_query, rule_go_conventions_one_query_gorm, rule_php_conventions_one_query, rule_go_postgres_no_manual_tx_in_repo [INFERRED 0.90]
- **Workflow транзакционен по умолчанию (O-8) — повтор в 5 файлах** — rule_layers_workflow_transactional_default, rule_go_conventions_write_atomicity, rule_go_workflow_transactional_default, rule_go_postgres_workflow_write_no_tx_error, rule_php_conventions_tx_manager_run [INFERRED 0.90]
- **Подтверждение коммита/пуша и запрет подписи ИИ — одно правило в 5 файлах** — rule_principles_commit_push_each_time, rule_git_commit_push_confirm, rule_self_check_commit_after_confirmation, rule_role_chain_db_and_commit_explicit_yes, rule_principles_no_ai_coauthor, rule_git_no_ai_trailers, rule_style_no_ai_in_code_git, rule_subagent_no_ai_signature [INFERRED 0.85]
- **Оркестрация /dev: авто-режим, фазы, субагенты, пределы параллельности и кругов ревью** — file_approaches_process_role_chain_md, file_skills_dev_skill_md, file_approaches_process_planning_md, file_approaches_process_subagent_md, topic_orchestrator_dev [INFERRED 0.85]
- **Группы файлов интерфейсов модуля домена (G-6)** — rule_decisions_g6_interface_file_groups, rule_checklist_interface_groups, rule_conventions_interface_file_groups, rule_domain_file_naming_table [INFERRED 0.90]
- **Workflow с записью транзакционен по умолчанию (O-8) — Go и PHP** — rule_workflow_tx_by_default, rule_checklist_p19_workflow_tx, rule_conventions_p16_tx_manager, rule_commands_orchestrator_tx_manager [INFERRED 0.85]
- **Запрет связей и expanded — 4 файла** — rule_mpscore_model_no_relations, rule_mpscore_repository_no_expanded, rule_mpscore_service_no_expanded, rule_review_findings_no_relations_no_expanded [INFERRED 0.85]
- **Правила транзакций PHP в 5 файлах** — rule_mpscore_transactions_workflow_always, rule_review_checks_workflow_transaction, rule_mpscore_service_no_repo_transaction, rule_mpscore_transactions_repo_transaction_antipattern, rule_review_findings_side_effects_outside_tx [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (205): approaches/patterns/performance.md, architecture/layers.md, regulations/entity.md, regulations/rest-api.md, stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/entrypoint.md, stacks/go/go-sdk/main.md, stacks/go/structure.md (+197 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (192): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, agents/df-security.md, approaches/process/analysis.md, approaches/process/code-navigation.md (+184 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (162): approaches/patterns/transactions.md, architecture/cross-domain.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/infra/postgres.md, stacks/go/go-sdk/workflow.md, stacks/php/examples/README.md, stacks/php/mps-core/capabilities.md (+154 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (137): approaches/patterns/async.md, approaches/patterns/cache.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/payments.md, stacks/go/checklist.md, stacks/go/go-sdk/entrypoint/rabbit.md (+129 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (102): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md (+94 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (79): approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/security.md, stacks/php/security.md, Секреты (ключи, JWT-секреты, креды) в Redis не кладутся, п.14 [О] Роль/право — middleware маршрута, не hasRole/authorize в контроллере/домене, п.29 [О] Скоуп владельца в каждом методе (index/show/update/destroy), выводится сервером; без умолчания = null (+71 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (73): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, п.36: нигде нет Claude/ИИ как автора (Co-Authored-By: Claude, «Generated with Claude Code»); текст без «ИИ-воды», п.22 [О] Только исключения ядра, не \Exception; сообщения клиенту по-русски; @throws, п.34 [В] Docblock класса — одна строка по-русски + @author; нигде Claude/ИИ как автор (Co-Authored-By: Claude), п.45 [И] Ранний выход вместо else/elseif; выбор по значению — match (+65 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (68): approaches/process/impact-analysis.md, approaches/process/self-check.md, approaches/process/testing.md, skills/dev-go/SKILL.md, skills/dev-php/SKILL.md, skills/dev-qa/SKILL.md, stacks/php/mps-core/tests.md, п.39: прогнаны go build, go vet, go test, gofmt -l (с горутинами -race); вывод показан (+60 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (67): approaches/patterns/migrations.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, regulations/contracts.md, regulations/technical-approval.md, stacks/php/mps-core/migrations.md, п.42: миграции, сиды, массовые правки, очистка — не запускать без явного «да» на каждую, п.41 [О] Миграция обратно совместима (expand/contract), FK явно, порядок выкатки описан (+59 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.06
Nodes (62): architecture/integrations.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/go-sdk/module.md (+54 more)

## Ambiguous Edges - Review These
- `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` → `Ревью ⇄ исправления — до 3 кругов`  [AMBIGUOUS]
  corpus/agents/df-review.md · relation: conflicts_with
- `Инвалидация — при записи в том же сценарии` → `Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `База MR — Target Branch этого MR (фичи идут в stage), не дефолт` → `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`  [AMBIGUOUS]
  corpus/regulations/git.md · relation: conflicts_with
- `Разработка: до 3 агентов параллельно, refactor/migrate — до 5 при нарезке без пересечений файлов` → `Одновременно не больше 3 агентов (refactor/migrate — до 5) в одном рабочем дереве, без isolation/worktree`  [AMBIGUOUS]
  corpus/skills/dev/SKILL.md · relation: conflicts_with
- `Новые вопросы по ходу: политика (а) по рекомендации с записью в rules-log — по умолчанию, (б) отложить кусок; согласование/внешний контракт — всегда отложить, действие с БД — стоп до «да»` → `Всё про правила — в rules-log.md; правила не правятся до разбора в Фазе 8`  [AMBIGUOUS]
  corpus/skills/dev/SKILL.md · relation: conflicts_with
- `Массовые операции, очистка, схема/миграции — только по явному «да» каждый раз, касается и локальной БД; на stage/prod — не выполнять вообще` → `Требует согласования любое изменение схемы БД — таблицы, поля, типы, FK, ограничения`  [AMBIGUOUS]
  corpus/regulations/technical-approval.md · relation: conflicts_with
- `п.22: panic — только при старте; обязательной настройки нет — не стартуем, не дефолт` → `п.9: panic() запрещён в production; допустим только при инициализации приложения`  [AMBIGUOUS]
  corpus/stacks/go/checklist.md · relation: conflicts_with
- `п.13: enum — {name}_enum.go, DTO — dto.go, сущности — entity.go; types.go/const.go/utils.go — [ВНИМАНИЕ]` → `Все DTO домена в одном dto.go; разбивать только если файл ~100+ строк`  [AMBIGUOUS]
  corpus/stacks/go/go-sdk/domain.md · relation: conflicts_with
- `п.22 [ОШИБКА]: не-бизнесовая функциональность (транспорт, сериализация, ретраи) не реализуется дважды — вынести в pkg/ или SDK` → `Workflow самодостаточен: общие хелперы шагов дублируются в каждый workflow, не шарятся`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `Список — paginate{Что}({E}FilterDto, Page); search(), filterSearch(), SearchDataObject, PaginatedQueryHelper в новом коде не используются (P-19)` → `QueryFilters — только V2 (V1 нельзя)`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/rules.md · relation: conflicts_with
- `CrudServiceDecorator есть с mps/core 1.7.0; при старом ядре (superapp-api 1.6.0) — extends Service, свой интерфейс, CRUD через репозиторий` → `TransactionManagerInterface/NoopTransactionManager в Core/ — только при mps/core < 1.10; с 1.10 — из ядра, своих нет`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/service.md · relation: conflicts_with
- `Метод репозитория — один запрос (тег repository-multi-query, [ОШИБКА])` → `Метод-обёртка над одной проверкой (findByIdOrFail, assertValid) — service-thin-wrapper [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/stacks/php/review-findings.md · relation: conflicts_with

## Knowledge Gaps
- **11 isolated node(s):** `Правила ревью по задетым слоям, иначе контекст 100k+ токенов`, `Файлы групп показывают, от чего зависит модуль; файлы по одному интерфейсу дробили модуль (screen-gateway: 34 файла в 7 модулях)`, `Новый корневой контекст разрывает цепочку и теряет trace ID, request ID, deadline`, `MR!151: два marshaler-а независимо чинят одну проблему watermill-amqp x-death и разъедутся`, `Каждый класс бага уже стрелял в проде/MR — повтор известного инцидента, не best practice` (+6 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` and `Ревью ⇄ исправления — до 3 кругов`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Инвалидация — при записи в том же сценарии` and `Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `База MR — Target Branch этого MR (фичи идут в stage), не дефолт` and `Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Разработка: до 3 агентов параллельно, refactor/migrate — до 5 при нарезке без пересечений файлов` and `Одновременно не больше 3 агентов (refactor/migrate — до 5) в одном рабочем дереве, без isolation/worktree`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Новые вопросы по ходу: политика (а) по рекомендации с записью в rules-log — по умолчанию, (б) отложить кусок; согласование/внешний контракт — всегда отложить, действие с БД — стоп до «да»` and `Всё про правила — в rules-log.md; правила не правятся до разбора в Фазе 8`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Массовые операции, очистка, схема/миграции — только по явному «да» каждый раз, касается и локальной БД; на stage/prod — не выполнять вообще` and `Требует согласования любое изменение схемы БД — таблицы, поля, типы, FK, ограничения`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `п.22: panic — только при старте; обязательной настройки нет — не стартуем, не дефолт` and `п.9: panic() запрещён в production; допустим только при инициализации приложения`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._