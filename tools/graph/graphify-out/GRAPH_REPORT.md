# Graph Report - dev-framework rules  (2026-10-17)

## Corpus Check
- 106 files · ~76,746 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1100 nodes · 4345 edges · 11 communities detected
- Extraction: 94% EXTRACTED · 6% INFERRED · 0% AMBIGUOUS · INFERRED: 246 edges (avg confidence: 0.82)
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
- [[_COMMUNITY_Очереди и идемпотентность|Очереди и идемпотентность]]

## God Nodes (most connected - your core abstractions)
1. `stacks/php/checklist.md` - 98 edges
2. `stacks/php/conventions.md` - 96 edges
3. `Репозиторий` - 95 edges
4. `stacks/go/checklist.md` - 87 edges
5. `stacks/php/structure.md` - 78 edges
6. `Транзакции` - 77 edges
7. `approaches/process/role-chain.md` - 75 edges
8. `skills/dev/SKILL.md` - 74 edges
9. `approaches/process/review.md` - 71 edges
10. `skills/dev-php/SKILL.md` - 68 edges

## Surprising Connections (you probably didn't know these)
- `«Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)` --conflicts_with--> `Ревью ⇄ исправления — до 3 кругов`  [AMBIGUOUS]
  corpus/agents/df-review.md → corpus/approaches/process/planning.md
- `Go: горутины только с ограничением параллелизма и отменой по ctx` --semantically_similar_to--> `Go: горутина с явным lifecycle, контекст от app, не context.Background()`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/async.md
- `Ошибки: временная — наверх и повтор; постоянная (битый payload, ошибка десериализации) — зафиксировать и подтвердить` --semantically_similar_to--> `Ошибки временные (сеть, 5xx — повтор) и постоянные (ручной разбор); тип в @throws`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/async.md → corpus/approaches/patterns/outbox.md
- `Инвалидация — при записи в том же сценарии` --conflicts_with--> `Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md → corpus/approaches/patterns/transactions.md
- `Сценарии включают «одновременно» и «повтор»` --semantically_similar_to--> `Архитектор отмечает раздел «Конкурентность» в design.md; нет одновременности — одна строка`  [INFERRED] [semantically similar]
  corpus/approaches/process/analysis.md → corpus/approaches/patterns/concurrency.md

## Hyperedges (group relationships)
- **Побочные эффекты только после коммита / через outbox** — rule_transactions_no_side_effects_in_tx, rule_outbox_no_send_in_tx, rule_performance_short_transaction, rule_concurrency_no_external_in_lock_tx, rule_payments_side_effects_outbox [INFERRED 0.85]
- **Статус без потребителя — тупик** — rule_payments_status_has_consumer, rule_impact_analysis_status_without_consumer, rule_idempotency_final_status_not_overwritten [INFERRED 0.80]
- **Правило «метод репозитория — один запрос» в общем и стековых файлах** — rule_layers_repo_one_query, rule_go_conventions_one_query_gorm, rule_php_conventions_one_query, rule_go_postgres_no_manual_tx_in_repo [INFERRED 0.90]
- **Workflow транзакционен по умолчанию (O-8) — повтор в 5 файлах** — rule_layers_workflow_transactional_default, rule_go_conventions_write_atomicity, rule_go_workflow_transactional_default, rule_go_postgres_workflow_write_no_tx_error, rule_php_conventions_tx_manager_run [INFERRED 0.90]
- **Лимит параллельных разработчиков 3/5 и агентов аудита 4–8** — rule_role_chain_parallel_3_refactor_5, rule_decisions_o20_task_size_limits, rule_dev_architect_parallel_limit_3_5, rule_security_audit_vs_dev_limit, rule_security_audit_4_8_parallel_explore [INFERRED 0.85]
- **O-19: контракт сохранить или /v2 без переключателей** — rule_decisions_o19_contract_question_no_toggles, rule_role_chain_refactor_contract_always_ask, rule_rest_api_refactor_contract_question, rule_stack_migration_contract_by_user_answer, rule_stack_migration_no_toggles, rule_dev_architect_contract_open_question, rule_dev_architect_feature_flag_new_only [INFERRED 0.90]
- **Подтверждение коммита/пуша и запрет подписи ИИ — одно правило в 5 файлах** — rule_principles_commit_push_each_time, rule_git_commit_push_confirm, rule_self_check_commit_after_confirmation, rule_role_chain_db_and_commit_explicit_yes, rule_principles_no_ai_coauthor, rule_git_no_ai_trailers, rule_style_no_ai_in_code_git, rule_subagent_no_ai_signature [INFERRED 0.85]
- **Жизненный цикл лога отклонений: запись по ходу → разбор Ф/П/О → правка через /dev-rule** — file_core_rules_evolution_md, file_skills_dev_rule_skill_md, file_skills_dev_skill_md, file_approaches_process_role_chain_md, topic_rules_log [INFERRED 0.90]
- **Оркестрация /dev: авто-режим, фазы, субагенты, пределы параллельности и кругов ревью** — file_approaches_process_role_chain_md, file_skills_dev_skill_md, file_approaches_process_planning_md, file_approaches_process_subagent_md, topic_orchestrator_dev [INFERRED 0.85]
- **Правила транзакций Go в трёх файлах** — rule_stacks_go_checklist_workflow_tx, rule_stacks_go_conventions_repo_one_query, rule_stacks_go_go_sdk_infra_postgres_workflow_tx_error [INFERRED 0.85]
- **Подтверждение коммита/пуша повторено в скиллах и памятке** — rule_skills_dev_go_no_commit_push, rule_skills_dev_php_no_commit_push, rule_skills_dev_mr_commit_push_each_yes, rule_skills_dev_commit_only_yes_no_ai, rule_stacks_go_checklist_commit_push_yes [INFERRED 0.90]
- **Workflow с записью транзакционен по умолчанию (O-8) — Go и PHP** — rule_workflow_tx_by_default, rule_checklist_p19_workflow_tx, rule_conventions_p16_tx_manager, rule_commands_orchestrator_tx_manager [INFERRED 0.85]
- **Запрет связей и expanded — 4 файла** — rule_mpscore_model_no_relations, rule_mpscore_repository_no_expanded, rule_mpscore_service_no_expanded, rule_review_findings_no_relations_no_expanded [INFERRED 0.85]
- **Правила транзакций PHP в 5 файлах** — rule_mpscore_transactions_workflow_always, rule_review_checks_workflow_transaction, rule_mpscore_service_no_repo_transaction, rule_mpscore_transactions_repo_transaction_antipattern, rule_review_findings_side_effects_outside_tx [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.03
Nodes (175): agents/df-analyst.md, agents/df-architect.md, agents/df-go.md, agents/df-php.md, agents/df-qa.md, agents/df-review.md, agents/df-security.md, approaches/process/analysis.md (+167 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.03
Nodes (154): approaches/process/mr-threads.md, approaches/process/review.md, core/style.md, regulations/code-review.md, regulations/git.md, skills/dev-mr/SKILL.md, skills/dev-review/SKILL.md, stacks/go/review-checks.md (+146 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.04
Nodes (116): architecture/integrations.md, architecture/layers.md, regulations/entity.md, stacks/go/conventions.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/infra/http.md, stacks/go/go-sdk/infra.md, stacks/go/structure.md (+108 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.05
Nodes (107): approaches/patterns/outbox.md, approaches/patterns/transactions.md, architecture/cross-domain.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/infra/postgres.md, stacks/go/go-sdk/workflow.md, stacks/php/mps-core/commands.md, stacks/php/mps-core/service.md (+99 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.04
Nodes (97): approaches/patterns/concurrency.md, approaches/process/self-check.md, approaches/process/testing.md, skills/dev-go/SKILL.md, skills/dev-qa/SKILL.md, stacks/go/checklist.md, stacks/php/mps-core/cli.md, stacks/php/mps-core/tests.md (+89 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.05
Nodes (96): approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/security.md, stacks/php/security.md, Секреты (ключи, JWT-секреты, креды) в Redis не кладутся, п.14 [О] Роль/право — middleware маршрута, не hasRole/authorize в контроллере/домене (+88 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (89): stacks/go/go-sdk/entrypoint/http.md, stacks/go/go-sdk/entrypoint.md, stacks/go/go-sdk/main.md, stacks/php/checklist.md, stacks/php/mps-core/data-objects.md, stacks/php/mps-core/enums.md, stacks/php/mps-core/swagger.md, stacks/php/review-findings.md (+81 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (84): core/principles.md, skills/dev-php/SKILL.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/module.md, stacks/php/conventions.md, stacks/php/examples/README.md (+76 more)

### Community 8 - "Стиль кода и текста"
Cohesion: 0.06
Nodes (65): approaches/patterns/async.md, approaches/patterns/idempotency.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/exceptions.md, stacks/php/mps-core/exceptions.md, stacks/php/mps-core/jobs.md, Асинхронность решает вызывающий код (workflow/entrypoint/runner); скрытая горутина/dispatch в сервисе — [ОШИБКА] (Go п.18), Consumer идемпотентен (at-least-once): проверка состояния, уникальный индекс по внешнему id события (+57 more)

### Community 9 - "Ошибки и HTTP вход"
Cohesion: 0.07
Nodes (65): approaches/patterns/cache.md, approaches/patterns/migrations.md, approaches/patterns/performance.md, regulations/technical-approval.md, stacks/go/go-sdk/infra/redis.md, stacks/php/mps-core/migrations.md, Cache-aside inline в workflow/сервисе-потребителе, не в репозитории и не в отдельном классе, Инвалидация — при записи в том же сценарии (+57 more)

### Community 10 - "Очереди и идемпотентность"
Cohesion: 0.07
Nodes (52): core/rules-evolution.md, skills/dev-rule/SKILL.md, stacks/vanilla-js/README.md, Памятка не заменяет правила: при расхождении главнее файл по ссылке, п.43 [О] Пробел или спор не решать молча: вопрос/решение по политике /dev + строка в rules-log.md, Пока спор открыт — в новом коде как в эталоне фреймворка (stacks/<stack>/examples/), это проговаривается пользователю, Локальные .claude/dev-rules.md проекта главнее правил фреймворка, Без коммита и пуша, пока пользователь явно не подтвердит; правила сам не правит (+44 more)

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
- `Мелкие шаги workflow допустимо повторить в соседнем workflow, не выносить в общий пакет` → `Дублирование скелета обработчика — предупреждение, вынести в State Machine/Strategy`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with
- `Хелперы шагов дублируются в каждый workflow; преобразования формата — всё равно один хелпер` → `п.46 [В] Преобразование формата — один раз: mps/utils → Core/Helpers/{Thing}Helper; вызов в prepareForValidation/Response, не сервис`  [AMBIGUOUS]
  corpus/stacks/go/review-findings.md · relation: conflicts_with
- `Список — paginate{Что}({E}FilterDto, Page); search(), filterSearch(), SearchDataObject, PaginatedQueryHelper в новом коде не используются (P-19)` → `QueryFilters — только V2 (V1 нельзя)`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/rules.md · relation: conflicts_with
- `CrudServiceDecorator есть с mps/core 1.7.0; при старом ядре (superapp-api 1.6.0) — extends Service, свой интерфейс, CRUD через репозиторий` → `TransactionManagerInterface/NoopTransactionManager в Core/ — только при mps/core < 1.10; с 1.10 — из ядра, своих нет`  [AMBIGUOUS]
  corpus/stacks/php/mps-core/service.md · relation: conflicts_with
- `Метод репозитория — один запрос (тег repository-multi-query, [ОШИБКА])` → `Метод-обёртка над одной проверкой (findByIdOrFail, assertValid) — service-thin-wrapper [ВНИМАНИЕ]`  [AMBIGUOUS]
  corpus/stacks/php/review-findings.md · relation: conflicts_with

## Knowledge Gaps
- **5 isolated node(s):** `Правила ревью по задетым слоям, иначе контекст 100k+ токенов`, `Связи создают неявные запросы и соблазн обойти Repository`, `$repo->transaction() — жёсткая связка оркестратора с чужим репозиторием, хуже тестируется и ломается при смене репозитория`, `Тег паттерна нужен для статистики повторяющихся ошибок автора MR`, `project_structure`
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
- **What is the exact relationship between `Мелкие шаги workflow допустимо повторить в соседнем workflow, не выносить в общий пакет` and `Дублирование скелета обработчика — предупреждение, вынести в State Machine/Strategy`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._