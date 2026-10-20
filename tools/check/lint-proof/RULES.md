# Правила, отданные анализаторам

Одна строка — одно правило фреймворка, которое проверяет анализатор (`core/rules-evolution.md`, «Статика первой»). Таблицу сверяет `python3 tools/check/lint-rules.py`: источник правила и тег существуют, правило есть в конфиге, у него есть случай в доказательстве, места запуска вызывают инструмент; и наоборот — каждое правило конфига и каждый случай доказательства есть в таблице. Текст правила остаётся в памятке и conventions — анализатор его не заменяет, а проверяет. На проекте эти правила гоняет `tools/lint/lint.py` конфигом фреймворка, без файлов в проекте (`tools/lint/README.md`).

Колонки: **Правило конфига** — для Go имя правила `depguard` или линтер/правило (`forbidigo`, `nlreturn`, `revive:<правило>`), для PHP — метод `LayersArchitecture` или `disallowed:<класс или функция>`; **Случаи** — имена из `CASES` скрипта доказательства через `;`; **Где запускается** — файлы, где вызывается прогон этого стека, через `;`.

## Go — `stacks/go/examples/tariff/.golangci.yml`, доказательство `tools/check/lint-proof/go.py`

| Правило (источник) | Тег | Правило конфига | Случаи | Где запускается |
|---|---|---|---|---|
| Домен не знает инфраструктуру, точки входа, workflow, сборку, ORM, HTTP (`stacks/go/checklist.md` п. 2) | `layer-dependency-forbidden` | domain-no-infra | domain→infrastructure; domain→entrypoints; domain→workflows; domain→app; domain→gorm; domain→gin; domain→net/http | `skills/dev-rule/SKILL.md` |
| Домены не импортируют друг друга (`stacks/go/checklist.md` п. 3) | `domain-cross-domain-access` | billing-no-other-domains; identity-no-other-domains; handbook-no-other-domains | billing→identity; identity→billing; handbook→billing | `skills/dev-rule/SKILL.md` |
| Workflow не вызывает workflow (`stacks/go/checklist.md` п. 8) | `workflow-calls-workflow` | workflow-no-workflow | workflow→workflow; разрешено: внешний тест workflow импортирует workflow | `skills/dev-rule/SKILL.md` |
| Workflow берёт интерфейсы доменов, не инфраструктуру (`architecture/layers.md`) | `layer-dependency-forbidden` | workflow-no-workflow | workflow→infrastructure | `skills/dev-rule/SKILL.md` |
| Точка входа не знает инфраструктуру и сборку (`stacks/go/checklist.md` п. 2) | `layer-dependency-forbidden` | entrypoint-no-infra | entrypoint→infrastructure; entrypoint→app | `skills/dev-rule/SKILL.md` |
| `internal/common` без зависимостей от слоёв (`architecture/layers.md`) | `common-with-logic` | common-no-internal | common→internal; разрешено: internal/common импортирует свой подпакет; разрешено: internal/common импортирует свой подпакет (импорт) | `skills/dev-rule/SKILL.md` |
| `context.Background()`/`TODO()` в бизнес-коде нет (`stacks/go/checklist.md` п. 18) | `context-not-propagated` | forbidigo | context.Background в домене | `skills/dev-rule/SKILL.md` |
| `ctx` — первым параметром (`stacks/go/checklist.md` п. 18) | `context-not-propagated` | revive:context-as-argument | ctx не первым | `skills/dev-rule/SKILL.md` |
| Аббревиатуры заглавными (`stacks/go/checklist.md` п. 34); `ALL_CAPS` и подчёркивания правило не запрещает — исключены | `initialism-case` | revive:var-naming | Id вместо ID; разрешено: константа ALL_CAPS; разрешено: имя с подчёркиванием | `skills/dev-rule/SKILL.md` |
| Пустая строка перед `return` (`stacks/go/checklist.md` п. 35) | `missing-blank-before-return` | nlreturn | нет пустой строки перед return | `skills/dev-rule/SKILL.md` |

## PHP — `stacks/php/examples/phpstan-rules.neon`, `tests/Architecture/LayersArchitecture.php`, доказательство `tools/check/lint-proof/php.py`

| Правило (источник) | Тег | Правило конфига | Случаи | Где запускается |
|---|---|---|---|---|
| Домен не знает `Infrastructure` (кроме Eloquent-модели, P-4), `Entrypoints`, `Workflows` (`architecture/layers.md`) | `layer-dependency-forbidden` | test_domain_does_not_know_outer_layers | домен → Infrastructure; домен → Entrypoints; домен → Workflows; разрешено: модель Eloquent в домене (P-4) | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| Домены не знают друг друга (`stacks/php/checklist.md` п. 3) | `domain-cross-domain-access` | test_billing_does_not_know_other_domains; test_catalog_does_not_know_other_domains | Billing → Catalog; Catalog → Billing | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| `Common/` без зависимостей от слоёв (`architecture/layers.md`) | `common-with-logic` | test_common_has_no_layer_dependencies | Common → Domains | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| Workflow не вызывает workflow (`stacks/php/checklist.md` п. 3) | `workflow-calls-workflow` | test_workflow_does_not_call_workflow | workflow → workflow | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| Точка входа не знает `Infrastructure` (кроме модели в Response, P-4) (`architecture/layers.md`) | `layer-dependency-forbidden` | test_entrypoint_does_not_know_infrastructure | точка входа → Infrastructure | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| Хранилище — только через Infrastructure: фасад `DB` (в т. ч. `DB::transaction`) вне Infrastructure, миграций и тестов (`architecture/layers.md`); `DB::transaction` внутри Infrastructure вне менеджера — проверяет агент (`stacks/php/checklist.md` п. 20) | `service-direct-db-query` | disallowed:Illuminate\Support\Facades\DB | фасад DB (DB::transaction) вне менеджера; разрешено: фасад DB в репозитории Infrastructure; разрешено: фасад DB в миграции; разрешено: фасад DB в тестах | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |
| `app()`/`resolve()` в бизнес-коде нет (`stacks/php/checklist.md` п. 5) | `di-resolve-in-business-code` | disallowed:app(); disallowed:resolve() | app() в бизнес-коде | `skills/dev-rule/SKILL.md`; `tools/php-check/run.sh` |

Не отданы анализаторам (шум или неточно), их проверяет агент: бросок `\Exception` (`disallowed-calls` не отличает бросок от перехвата), затенение (`govet shadow` ловит разрешённые идиомы), длина функций, дубли кода, смысл имён, транзакции и гонки по смыслу, скоуп, бизнес-логика.
