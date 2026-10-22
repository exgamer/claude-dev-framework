# Graph Report - dev-framework rules  (2026-10-22)

## Corpus Check
- 111 files · ~120,207 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1232 nodes · 4998 edges · 9 communities detected
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 137 edges (avg confidence: 0.83)
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
2. `core/backlog.md` - 101 edges
3. `stacks/php/checklist.md` - 97 edges
4. `approaches/process/review-tags.md` - 95 edges
5. `stacks/go/checklist.md` - 92 edges
6. `skills/dev-php/SKILL.md` - 86 edges
7. `транзакции` - 82 edges
8. `stacks/php/structure.md` - 81 edges
9. `skills/dev-go/SKILL.md` - 80 edges
10. `репозиторий` - 79 edges

## Surprising Connections (you probably didn't know these)
- `Аналитик-субагент пользователя не спрашивает: неясности — в «Неясности» requirements.md и в итог` --semantically_similar_to--> `Архитектор-субагент не выполняет Шаг 5 (вопросы пользователю): вопросы — в «Открытые вопросы» design.md и в итог`  [INFERRED] [semantically similar]
  corpus/agents/df-analyst.md → corpus/agents/df-architect.md
- `Транзакция короткая: без внешних HTTP, публикаций, файлов, sleep` --semantically_similar_to--> `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [INFERRED] [semantically similar]
  corpus/approaches/patterns/performance.md → corpus/approaches/patterns/transactions.md
- `В Go сервис домена — конкретный тип (workflow в волне 2); в PHP ServiceInterface — вызывающий код в волне 1` --semantically_similar_to--> `По умолчанию сервисы без интерфейса (Go — конкретный тип); PHP — исключение: {E}ServiceInterface у каждого сервиса`  [INFERRED] [semantically similar]
  corpus/approaches/process/planning.md → corpus/regulations/contracts.md
- `Конкурентные места — интеграционный тест с параллельными запросами на реальной БД; unit ничего не доказывает` --semantically_similar_to--> `DoD: ревью ПРОЙДЕНО (0 [ОШИБКА], 0 [БЕЗОПАСНОСТЬ]); каждый AC* покрыт тестом; govulncheck/composer audit; go test -race чистый`  [INFERRED] [semantically similar]
  corpus/approaches/process/testing.md → corpus/approaches/process/role-chain.md
- `response.ErrorResponse: статус по ErrorKind; не-AppException — 500 с текстом ошибки в message` --semantically_similar_to--> `Техническая ошибка драйвера текстом в message через ErrorResponse — не [БЕЗОПАСНОСТЬ], уровень по каталогу; закрывать ли — выбор разработчика`  [INFERRED] [semantically similar]
  corpus/stacks/go/go-sdk/capabilities.md → corpus/stacks/go/security.md

## Hyperedges (group relationships)
- **Форма outbox: запись в транзакции, Command доставки, повтор SKIP LOCKED** — file_approaches_patterns_outbox_md, file_stacks_go_examples_tariff_readme_md, file_approaches_process_review_tags_md, topic_outbox [INFERRED 0.85]
- **Побочные эффекты после коммита / outbox** — rule_transactions_no_side_effects_inside_tx, rule_outbox_no_send_in_tx_no_own_broker, rule_performance_short_transaction, rule_concurrency_short_work_in_lock_tx [INFERRED 0.85]
- **Уровень находки — только каталог тегов (O-24)** — rule_review_tags_level_only_in_catalog, rule_decisions_o24_level_only_catalog, rule_review_inherited_warning_3a, rule_review_tags_inherited_is_warning [INFERRED 0.90]
- **Безопасность: каталог тегов + признаки по стекам + памятки** — file_approaches_process_security_md, file_stacks_go_security_md, file_stacks_php_security_md, file_stacks_go_checklist_md, file_stacks_php_checklist_md [INFERRED 0.85]
- **Запрет коммита/пуша без подтверждения в ролях** — rule_subagent_no_commit_push_db, rule_self_check_commit_after_confirmation, rule_dev_go_no_commit_without_confirm, rule_dev_php_no_commit_without_confirm, rule_dev_review_offer_fix_errors, rule_review_fix_only_direct_run [INFERRED 0.85]
- **Подтверждение коммита/пуша в 5 файлах** — rule_principles_commit_push_confirm, rule_rules_evolution_commit_confirm, rule_dev_rule_commit_confirm, rule_go_checklist_commit_push, rule_php_checklist_commit_push [INFERRED 0.85]
- **Переход состояния: свой эндпоинт/Command, условный UPDATE, без статуса в PATCH/PUT** — rule_entity_transition_own_command, rule_rest_api_patch_put_no_status, rule_go_checklist_patch_no_status, rule_php_checklist_patch_no_status, rule_review_tags_status_in_update_request [INFERRED 0.90]
- **Цепочка согласования схемы БД: миграции → регламент → архитектор → /dev → ревью** — rule_migrations_schema_change_needs_approval, rule_technical_approval_any_db_change, rule_technical_approval_migration_strictly_by_schema, rule_dev_architect_schema_section, rule_dev_migration_tasks_need_schema_yes, rule_review_migration_schema_not_approved, rule_role_chain_schema_change_approval [INFERRED 0.85]

## Communities

### Community 0 - "Роли, агенты, процесс"
Cohesion: 0.02
Nodes (257): architecture/cross-domain.md, architecture/layers.md, regulations/entity.md, regulations/rest-api.md, skills/dev-php/SKILL.md, stacks/go/examples/tariff/README.md, stacks/go/go-sdk/domain.md, stacks/go/go-sdk/entrypoint/http.md (+249 more)

### Community 1 - "Транзакции и конкурентность"
Cohesion: 0.02
Nodes (205): agents/df-analyst.md, agents/df-go.md, agents/df-php.md, approaches/process/analysis.md, approaches/process/bugfix.md, approaches/process/planning.md, approaches/process/role-chain.md, approaches/process/subagent.md (+197 more)

### Community 2 - "Ревью и безопасность"
Cohesion: 0.03
Nodes (156): approaches/patterns/async.md, approaches/patterns/concurrency.md, approaches/patterns/idempotency.md, approaches/patterns/outbox.md, approaches/patterns/performance.md, approaches/patterns/transactions.md, stacks/go/go-sdk/entrypoint/rabbit.md, stacks/go/go-sdk/infra.md (+148 more)

### Community 3 - "Разработчик, тесты, Go-памятка"
Cohesion: 0.04
Nodes (147): agents/df-review.md, approaches/process/mr-threads.md, approaches/process/review.md, approaches/process/review-tags.md, approaches/process/self-check.md, core/backlog.md, core/principles.md, regulations/code-review.md (+139 more)

### Community 4 - "Слои, домен, DTO"
Cohesion: 0.03
Nodes (144): agents/df-security.md, approaches/patterns/payments.md, approaches/process/security.md, regulations/service-requirements.md, skills/dev-security/SKILL.md, stacks/go/go-sdk/exceptions.md, stacks/go/review-findings.md, stacks/go/security.md (+136 more)

### Community 5 - "Кэш, DI, модули SDK"
Cohesion: 0.03
Nodes (115): approaches/patterns/cache.md, architecture/integrations.md, skills/dev-go/SKILL.md, stacks/go/conventions.md, stacks/go/go-sdk/app.md, stacks/go/go-sdk/capabilities.md, stacks/go/go-sdk/di.md, stacks/go/go-sdk/infra/http.md (+107 more)

### Community 6 - "Git, MR, эволюция правил"
Cohesion: 0.05
Nodes (104): agents/df-architect.md, approaches/patterns/migrations.md, approaches/process/code-navigation.md, approaches/process/impact-analysis.md, approaches/process/legacy-refactor.md, approaches/process/stack-migration.md, regulations/contracts.md, regulations/technical-approval.md (+96 more)

### Community 7 - "REST, Swagger, PHP-памятка"
Cohesion: 0.06
Nodes (60): core/style.md, stacks/go/style.md, stacks/php/style.md, stacks/qa-playwright/conventions.md, Читается сверху вниз, глубина блоков ≤ 2, вызов не вкладывается в вызов, Читается сверху вниз, глубина блоков ≤ 2, ->when() → if, Форма — по эталону stacks/go/examples; сосед/легаси при расхождении — не образец, Форма — по эталону stacks/php/examples; сосед/легаси при расхождении — не образец (+52 more)

### Community 8 - "Community 8"
Cohesion: 0.09
Nodes (44): agents/df-qa.md, approaches/process/testing.md, skills/dev-qa/SKILL.md, stacks/go/structure.md, stacks/php/mps-core/tests.md, Смена кода или текста ответа: связи ищутся поиском по всем *_test.go, Миграции, сиды, массовые правки, очистка — не запускать без явного «да» на каждую, Миграции, сиды, массовые правки, migrate:fresh/RefreshDatabase на общей БД — не без явного «да» на каждую (+36 more)

## Ambiguous Edges - Review These
- `Инвалидация — при записи, в том же сценарии, что меняет данные` → `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`  [AMBIGUOUS]
  corpus/approaches/patterns/cache.md · relation: conflicts_with
- `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` → `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`  [AMBIGUOUS]
  corpus/stacks/go/structure.md · relation: conflicts_with
- `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` → `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`  [AMBIGUOUS]
  corpus/core/style.md · relation: conflicts_with

## Knowledge Gaps
- **10 isolated node(s):** `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Флаг — две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения`, `Знание о статусах в одном месте: сервис, сравнивающий статусы сам, расходится с соседним при первой правке`, `page 0 при uint даёт переполнение смещения (page − 1) × per_page — приводить к 1 при разборе запроса` (+5 more)
  These have ≤1 connection - possible missing edges or undocumented components.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Инвалидация — при записи, в том же сценарии, что меняет данные` and `Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B` and `Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **What is the exact relationship between `Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»` and `PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда`?**
  _Edge tagged AMBIGUOUS (relation: conflicts_with) - confidence is low._
- **Why does `core/backlog.md` connect `Разработчик, тесты, Go-памятка` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Ревью и безопасность`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`, `Community 8`?**
  _High betweenness centrality (0.069) - this node is a cross-community bridge._
- **Why does `core/style.md` connect `REST, Swagger, PHP-памятка` to `Роли, агенты, процесс`, `Транзакции и конкурентность`, `Ревью и безопасность`, `Разработчик, тесты, Go-памятка`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `Community 8`?**
  _High betweenness centrality (0.055) - this node is a cross-community bridge._
- **Why does `stacks/php/checklist.md` connect `Роли, агенты, процесс` to `Транзакции и конкурентность`, `Ревью и безопасность`, `Разработчик, тесты, Go-памятка`, `Слои, домен, DTO`, `Кэш, DI, модули SDK`, `Git, MR, эволюция правил`, `REST, Swagger, PHP-памятка`, `Community 8`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **What connects `Прочитанный целиком файл оплачивается на всех следующих шагах (96% токенов — повторное чтение)`, `Флаг — две живые реализации за одним адресом, старую забывают удалить; версия видна в URL и логах`, `Агенту проще заглушить проверку, чем исправить; заглушка прячет следующие нарушения` to the rest of the system?**
  _10 weakly-connected nodes found - possible documentation gaps or missing edges._