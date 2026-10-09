# dev-framework

Фреймворк для Claude Code, который пишет код так, как пишет автор: по регламентам `backend-regulation`, по архитектуре Go SDK и mps/core и по реальному стилю эталонных проектов. Существует отдельно от `claude-skills`.

## Слои

```
core/            принципы, стиль автора, развитие правил, decisions.md (открытые споры + ключевые решения), decisions-log.md (полная история)
regulations/     выжимки регламентов про код (REST, git, сущности, контракты, согласования, требования к сервису)
architecture/    слои, связи доменов, интеграции — без привязки к стеку
approaches/
  patterns/      транзакции, outbox, кэш, идемпотентность, асинхронность, миграции, производительность, конкурентность (data race, race condition)
  process/       цепочка ролей, анализ требований, карта связей, планирование и параллельная работа, рефактор легаси, перенос между стеками, самопроверка, ревью, безопасность
stacks/
  go/            Go + Go SDK (gosdk-*): checklist (памятка), structure, conventions, style, review-findings, examples/ (эталонный модуль)
                 + go-sdk/ (как пользоваться SDK, capabilities.md — что есть, reference/ — полный API, official/ — README модулей)
  php/           Laravel + mps/core + mps/utils: checklist (памятка), structure, conventions, style, review-findings, examples/ (эталонный модуль)
                 + mps-core/ (как пользоваться ядром, capabilities.md — что есть, reference/ — полный API по версиям, official/ — docs пакетов)
  vanilla-js/    фронт без фреймворка — отложен до проекта-образца
  qa-playwright/ UI-автотесты
skills/          точки входа (роли)
agents/          агенты df-* — роли как субагенты /dev (общие правила — approaches/process/subagent.md)
tools/sdk-ref/   генераторы справочников SDK/ядра из исходников (go.sh, php.sh)
install.sh       симлинки в ~/.claude (фреймворк, скиллы, агенты)
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

В цепочке `/dev` роли выполняют агенты `df-*` (`agents/`) — каждый в свежем контексте, с ограниченными инструментами; таблица — `approaches/process/role-chain.md`, «Контекст ролей». Напрямую их вызывать не нужно.

## Сценарии

- **Новая фича:** `/dev <задача>` → один список вопросов → ответы → готовый результат (дизайн, угрозы, код, ревью, тесты, отчёт); коммит — по «да».
- **Рефактор легаси:** `/dev отрефактори <модуль>` → `approaches/process/legacy-refactor.md`: фиксация поведения тестами, мелкие шаги, strangler, контракт не меняется.
- **Перенос на другой стек:** `/dev перенеси <модуль> из <php> в <go>` → `approaches/process/stack-migration.md`: спека поведения из кода, маппинг Go SDK ↔ mps/core, контракт сохраняется.

## Источник истины

Фреймворк правится **только в этом репозитории** (где бы он ни был склонирован; у автора — `~/AIProjects/dev-framework`). `~/.claude/dev-framework`, `~/.claude/skills/dev-*`, `~/.claude/agents/df-*` и копии в проектах — производные, обновляются из него (`install.sh`). Подробно — `core/rules-evolution.md`, «Где правится фреймворк».

## Установка и настройка

### Что нужно

| Что | Зачем | Обязательно |
|---|---|---|
| Claude Code | всё остальное работает внутри него | да |
| `git`, `bash` | клонирование, `install.sh` | да |
| Go + доступ к `git.mpinnovations.kz/mps/go-packages` (`GOPRIVATE`, ключ/токен для git) | сборка и тесты Go-проектов, справочник SDK (`tools/sdk-ref/go.sh`) | для Go |
| PHP + Composer или Docker-контейнер проекта | `csfix`, `phpstan`, тесты PHP-проектов | для PHP |
| `python3` | справочник ядра PHP (`tools/sdk-ref/php.sh`) | для пересборки справочника |
| `glab` (авторизованный) | ревью MR: `/dev-review --mr` | для ревью MR |

### 1. Скачать и установить

```bash
git clone https://github.com/exgamer/claude-dev-framework.git ~/AIProjects/dev-framework
cd ~/AIProjects/dev-framework
./install.sh
```

`install.sh` ставит **симлинки** на репозиторий, ничего не копирует и не удаляет:

| Ссылка | На что |
|---|---|
| `~/.claude/dev-framework` | весь репозиторий (правила читаются отсюда) |
| `~/.claude/skills/dev`, `dev-*` | `skills/*` — команды `/dev`, `/dev-go`, … |
| `~/.claude/agents/df-*.md` | `agents/*` — агенты, которых запускает `/dev` |

Если путь уже занят не нашей ссылкой, скрипт пишет `skip` и не трогает его — разберитесь вручную (обычно это старая копия: удалить её и запустить `install.sh` ещё раз). Повторный запуск безопасен.

### 2. Триггеры команд

В конце `install.sh` печатает строки вида
```
When the user types `/dev`, invoke the Skill tool with `skill: "dev"` before doing anything else.
```
Добавьте их в `~/.claude/CLAUDE.md` (по желанию с описанием, как у остальных скиллов). Скиллы видны и без этого, но так команда `/dev…` срабатывает надёжнее.

### 3. Перезапустить Claude Code и проверить

Скиллы и агенты загружаются при старте сессии.

- `/dev` и `/dev-*` есть в списке команд;
- `/agents` показывает `df-analyst`, `df-architect`, `df-security`, `df-qa`, `df-go`, `df-php`, `df-review`;
- `readlink ~/.claude/dev-framework` указывает на склонированный репозиторий.

### 4. Настройка проекта (по желанию)

- **Правила проекта** — `.claude/dev-rules.md` в корне проекта: особенности сервиса, которые главнее фреймворка (`core/rules-evolution.md`). Не создаётся заранее — появляется при разборе лога отклонений после задачи.
- **Справочник SDK/ядра под версию проекта** — если версии в проекте отличаются от тех, по которым собран `reference/` (указаны в шапке файлов); роли сами предложат пересобрать — команды в разделе «Справочники SDK».
- **Копия фреймворка в проекте** (`<project>/.claude/dev-framework`) — только если проект должен жить с зафиксированной версией правил; она главнее `~/.claude/dev-framework` и не правится на месте.

### Первый запуск

```
/dev <задача или номер из трекера>
```
`/dev` изучит проект, задаст все вопросы одним списком и после ответов доведёт до результата. Коммит, пуш, миграции и действия с БД — только по вашему «да».

### Обновление

```bash
cd ~/AIProjects/dev-framework && git pull && ./install.sh
```
Правки существующих файлов видны сразу (симлинки); `install.sh` нужен, когда появились новые скиллы или агенты — после него перезапустить Claude Code и добавить новые триггеры из его вывода.

### Удаление

```bash
rm ~/.claude/dev-framework ~/.claude/skills/dev ~/.claude/skills/dev-* ~/.claude/agents/df-*.md
```
Удаляются только ссылки; строки `/dev*` из `~/.claude/CLAUDE.md` убрать вручную.

### Где править

Фреймворк правится только в репозитории; `~/.claude/*` — ссылки на него. Подробно — раздел «Источник истины» выше и `core/rules-evolution.md`.

## Справочники SDK

`stacks/go/go-sdk/reference/` и `stacks/php/mps-core/reference/` генерируются из исходников и не правятся руками:

```bash
tools/sdk-ref/go.sh  stacks/go/go-sdk/reference --latest                 # свежий внутренний SDK из module cache
tools/sdk-ref/go.sh  <dir> --gomod <project>/go.mod                       # версии конкретного проекта
tools/sdk-ref/php.sh stacks/php/mps-core/reference <project-dir>          # mps/core + mps/utils из vendor проекта
```

Официальная документация (`official/`) копируется из пакетов той же версии (`docs/`, README).

После обновления справочника — пройтись по `capabilities.md` (что появилось/устарело) и по примерам `examples/`.
