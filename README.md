# dev-framework

Фреймворк для Claude Code, который пишет код так, как пишет автор: по регламентам `backend-regulation`, по архитектуре Go SDK и mps/core и по реальному стилю эталонных проектов. Существует отдельно от `claude-skills`.

## Слои

```
core/            принципы, стиль автора, развитие правил, журнал решений и открытых споров
regulations/     выжимки регламентов про код (REST, git, сущности, контракты, согласования, требования к сервису)
architecture/    слои, связи доменов, интеграции — без привязки к стеку
approaches/
  patterns/      транзакции, outbox, кэш, идемпотентность, асинхронность, миграции, производительность, конкурентность (data race, race condition)
  process/       цепочка ролей, анализ требований, карта связей, планирование и параллельная работа, рефактор легаси, перенос между стеками, самопроверка, ревью, безопасность
stacks/
  go/            Go + Go SDK (gosdk-*): structure, conventions, style, review-findings, examples/ (эталонный модуль)
                 + go-sdk/ (как пользоваться SDK, capabilities.md — что есть, reference/ — полный API, official/ — README модулей)
  php/           Laravel + mps/core + mps/utils: structure, conventions, style, review-findings, examples/ (эталонный модуль)
                 + mps-core/ (как пользоваться ядром, capabilities.md — что есть, reference/ — полный API по версиям, official/ — docs пакетов)
  vanilla-js/    фронт без фреймворка — отложен до проекта-образца
  qa-playwright/ UI-автотесты
skills/          точки входа (роли)
tools/sdk-ref/   генераторы справочников SDK/ядра из исходников (go.sh, php.sh)
install.sh       симлинки в ~/.claude
```

Порядок приоритета правил: локальные правила проекта (`.claude/dev-rules.md`) > стек > архитектура > core.
Главное правило: спорное, не описанное и «сделано в проекте, но не подкреплено регламентом» не решается молча (см. `core/principles.md`).

## Эталоны

| Стек | Основной | Второй |
|---|---|---|
| Go + Go SDK | `~/GoSdkProjects/parking-session-service` | `~/GoSdkProjects/go-sdk-rest-template` |
| Laravel + mps/core | `superapp-api/parking_app` | `superapp-api/super_app` |

## Роли (скиллы)

| Скилл | Роль | Статус |
|---|---|---|
| `/dev` | главная точка входа: изучает проект, задаёт все вопросы одним списком, после ответов доводит до результата (дизайн, угрозы, код до 3 параллельно, ревью с исправлениями, тесты); `--step` — пошагово | готов |
| `/dev-analyst` | требования из задачи (в т.ч. бизнесовой): участники, сценарии, правила, критерии приёмки, границы, неясности | готов |
| `/dev-architect` | тех. дизайн, API, домены/модули, согласования, план с волнами; режимы feature / refactor / migrate | готов |
| `/dev-go` | разработчик Go + Go SDK | готов |
| `/dev-php` | разработчик Laravel + mps/core + mps/utils | готов |
| `/dev-security` | модель угроз до кода (`design` → `threats.md`) и глубокий аудит сервиса (`audit`) | готов |
| `/dev-review` | ревью Go/PHP по тем же правилам + «не покрыто правилами» | готов |
| `/dev-qa` | тест-кейсы, тесты стека, UI-автотесты | готов |
| `/dev-rule` | обсудить пробел/спор и дописать правила; разбор лога отклонений задачи (`/dev-rule <rules-log.md>`) | готов |
| `/dev-front` | разработчик vanilla JS | отложен |

## Сценарии

- **Новая фича:** `/dev <задача>` → один список вопросов → ответы → готовый результат (дизайн, угрозы, код, ревью, тесты, отчёт); коммит — по «да».
- **Рефактор легаси:** `/dev отрефактори <модуль>` → `approaches/process/legacy-refactor.md`: фиксация поведения тестами, мелкие шаги, strangler, контракт не меняется.
- **Перенос на другой стек:** `/dev перенеси <модуль> из <php> в <go>` → `approaches/process/stack-migration.md`: спека поведения из кода, маппинг Go SDK ↔ mps/core, контракт сохраняется.

## Источник истины

Фреймворк правится **только в этом репозитории** (где бы он ни был склонирован; у автора — `~/AIProjects/dev-framework`). `~/.claude/dev-framework`, `~/.claude/skills/dev-*` и копии в проектах — производные, обновляются из него (`install.sh`). Подробно — `core/rules-evolution.md`, «Где правится фреймворк».

## Установка

`./install.sh` — симлинк репозитория в `~/.claude/dev-framework` и скиллов в `~/.claude/skills/`; существующее не трогает. Строки триггеров для `~/.claude/CLAUDE.md` печатает в конце.

## Справочники SDK

`stacks/go/go-sdk/reference/` и `stacks/php/mps-core/reference/` генерируются из исходников и не правятся руками:

```bash
tools/sdk-ref/go.sh  stacks/go/go-sdk/reference --latest                 # свежий внутренний SDK из module cache
tools/sdk-ref/go.sh  <dir> --gomod <project>/go.mod                       # версии конкретного проекта
tools/sdk-ref/php.sh stacks/php/mps-core/reference <project-dir>          # mps/core + mps/utils из vendor проекта
```

Официальная документация (`official/`) копируется из пакетов той же версии (`docs/`, README).

После обновления справочника — пройтись по `capabilities.md` (что появилось/устарело) и по примерам `examples/`.
