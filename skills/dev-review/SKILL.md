---
name: dev-review
description: "Ревьюер dev-framework: ревью Go (Go SDK) и PHP (Laravel + mps/core) по тем же правилам, что у разработчика — безопасность, архитектура, SDK, проверки за пределами дифа, MR через glab, отчёт с тегами для auto-review (глубокий аудит — /dev-security audit)"
trigger: /dev-review
---

# /dev-review

`{DF}` — `.claude/dev-framework` в проекте, если есть, иначе `~/.claude/dev-framework`.

```
/dev-review                         # изменённые файлы (git diff HEAD)
/dev-review --staged | <path> | --module <name> | --all
/dev-review --mr <id-or-url>        # MR через glab, с открытыми тредами ревьюеров
/dev-review --security-audit [path] # синоним /dev-security audit — выполняй по {DF}/skills/dev-security/SKILL.md
```

## Шаг 1 — Процедура и стек

Прочитай `{DF}/approaches/process/review.md` — там цели ревью (в т.ч. `--mr`), порядок проверок, уровни, теги, формат отчёта, сохранение. Выполняй его по шагам. `--security-audit` — это роль `/dev-security`, режим `audit`.

Стек: `go.mod` с `gosdk-*` → Go, `composer.json` с `mps/core` → PHP. Смешанный дифф — каждый стек по своим файлам.

## Шаг 2 — Правила стека

- Go: `{DF}/stacks/go/{structure,conventions,style,review-findings,security,review-checks}.md`, `{DF}/stacks/go/go-sdk/capabilities.md`, нужные `go-sdk/*.md`, эталон `{DF}/stacks/go/examples/tariff/`.
- PHP: `{DF}/stacks/php/{structure,conventions,style,review-findings,security,review-checks,review-tags}.md`, `{DF}/stacks/php/mps-core/capabilities.md`, нужные `mps-core/*.md`, эталон `{DF}/stacks/php/examples/`.
- Всегда: `{DF}/core/*.md`, `{DF}/architecture/*.md`, `{DF}/regulations/*.md`; локальные `.claude/dev-rules.md` проекта главнее.
- API сверять со справочником версии проекта (`reference/`; Go — версии из `go.mod`, PHP — из `composer.lock`), при отсутствии — пересобрать `{DF}/tools/sdk-ref/`.

## Шаг 3 — Ревью, отчёт, сохранение

По `review.md`. Обязательно:
- **[БЕЗОПАСНОСТЬ]** по `security.md` стека — всегда КРИТИЧНО; есть `docs/tasks/<ключ>/threats.md` — каждая мера реализована (не реализована — [БЕЗОПАСНОСТЬ] с тегом угрозы);
- блок «Не покрыто правилами» — темы для обсуждения, не ошибки автора;
- известные споры (`{DF}/core/decisions.md`) — как спор, не как находку;
- колонка категории в сохранённом отчёте — kebab-case тег (читает `auto-review`);
- отчёт — `review_result/{YYYY-MM-DD_HH-MM}_{type}.md` (или `docs/tasks/<ключ>/review.md` в цепочке `/dev`).

## Шаг 4 — Итог

Путь к отчёту, вердикт (ПРОЙДЕНО / НУЖНЫ ПРАВКИ / КРИТИЧНО), предложение исправить [ОШИБКИ]. Новый повторяющийся класс замечаний — предложить `/dev-rule` в `review-findings.md` стека. Коммит/пуш — только по явному подтверждению.
