#!/usr/bin/env bash
# Установка dev-framework в проект: симлинк фреймворка в ~/.claude/dev-framework (правила; токенов не стоит),
# скиллов в <проект>/.claude/skills/, агентов в <проект>/.claude/agents/. Только в проект, не в ~/.claude: описания
# скиллов и агентов грузятся в каждую сессию, а нужны только в рабочих проектах.
# Источник истины — этот репозиторий: правки только здесь, симлинки подхватывают их сразу; новый скилл или агент —
# повторный запуск. Ничего не удаляет: если цель уже существует и это не наш симлинк — пропускает и сообщает.
#   ./install.sh <папка проекта>
#   ./install.sh --remove <папка проекта>   — убрать симлинки фреймворка из проекта
set -euo pipefail

DF_SRC="$(cd "$(dirname "$0")" && pwd -P)"   # -P: при запуске через ~/.claude/dev-framework — тот же путь, что в симлинках
CLAUDE_DIR="${HOME}/.claude"
remove=0
[ "${1:-}" = "--remove" ] && { remove=1; shift; }
[ $# -eq 1 ] || { echo "использование: $0 [--remove] <папка проекта>" >&2; exit 2; }
PROJECT="$(cd "$1" && pwd)"
[ "$PROJECT" = "$HOME" ] && { echo "папка проекта не может быть домашней (это была бы установка во все проекты)" >&2; exit 2; }
SKILLS_DIR="${PROJECT}/.claude/skills"
AGENTS_DIR="${PROJECT}/.claude/agents"

if [ $remove = 1 ]; then
  for dst in "$SKILLS_DIR"/dev "$SKILLS_DIR"/dev-* "$AGENTS_DIR"/df-*.md; do
    if [ -L "$dst" ] && [[ "$(readlink "$dst")" == "$DF_SRC"/* ]]; then rm "$dst"; echo "removed $dst"; fi
  done
  exit 0
fi

link() {
  local src="$1" dst="$2"

  if [ -L "$dst" ]; then
    if [ "$(readlink "$dst")" = "$src" ]; then
      echo "ok      $dst"
      return
    fi
    echo "skip    $dst — симлинк на другое место: $(readlink "$dst")"
    return
  fi

  if [ -e "$dst" ]; then
    echo "skip    $dst — уже существует и это не симлинк"
    return
  fi

  ln -s "$src" "$dst"
  echo "linked  $dst -> $src"
}

mkdir -p "$CLAUDE_DIR" "$SKILLS_DIR" "$AGENTS_DIR"
link "$DF_SRC" "${CLAUDE_DIR}/dev-framework"

for skill in "$DF_SRC"/skills/*/; do
  name="$(basename "$skill")"
  link "${skill%/}" "${SKILLS_DIR}/${name}"
done

for agent in "$DF_SRC"/agents/*.md; do
  link "$agent" "${AGENTS_DIR}/$(basename "$agent")"
done

# симлинки — личная установка, в репозиторий проекта не попадают
if git -C "$PROJECT" rev-parse --git-dir >/dev/null 2>&1; then
  ex="$(git -C "$PROJECT" rev-parse --git-path info/exclude)"; [[ "$ex" = /* ]] || ex="$PROJECT/$ex"
  prefix="$(git -C "$PROJECT" rev-parse --show-prefix)"   # проект в подпапке репозитория: пути в exclude — от корня
  for pat in "/${prefix}.claude/skills/dev" "/${prefix}.claude/skills/dev-*" "/${prefix}.claude/agents/df-*.md"; do
    grep -qxF "$pat" "$ex" 2>/dev/null || echo "$pat" >> "$ex"
  done
  echo "git     симлинки — в .git/info/exclude"
fi

# Анализаторы — на машину, не в проект: правила фреймворка гоняет tools/lint/lint.py своим конфигом (tools/lint/README.md)
if [ -f "$PROJECT/go.mod" ]; then
  if command -v golangci-lint >/dev/null 2>&1 && golangci-lint version 2>/dev/null | grep -q "version 2\."; then
    echo "lint    golangci-lint v2 — есть"
  else
    echo "lint    golangci-lint v2 не найден — нужен для правил фреймворка на Go-проекте (tools/lint/lint.py go)"
    if command -v brew >/dev/null 2>&1 && [ -t 0 ]; then
      read -r -p "        поставить через brew (brew install golangci-lint)? [y/N] " answer
      if [ "$answer" = "y" ] || [ "$answer" = "Y" ]; then
        brew install golangci-lint
      fi
    else
      echo "        поставить: brew install golangci-lint (или https://golangci-lint.run/welcome/install/)"
    fi
  fi
fi
if [ -f "$PROJECT/composer.json" ]; then
  if command -v docker >/dev/null 2>&1; then
    echo "lint    PHP: инструменты фреймворка (phpstan, phpat, disallowed-calls) поставит tools/lint/lint.py php при первом запуске — в PHP-контейнер проекта, в /tmp/df-lint, не в проект"
  else
    echo "lint    PHP: нужен docker с PHP-контейнером проекта — в нём tools/lint/lint.py php гоняет правила фреймворка"
  fi
fi

echo
echo "Стопы для ~/.claude/settings.json (добавить вручную, если ещё нет) — спрашивать перед коммитом, пушем и разрушающим SQL:"
cat <<'JSON'
  "permissions": {
    "ask": [
      "Bash(git commit *)", "Bash(git push *)", "Bash(git -C * commit *)", "Bash(git -C * push *)",
      "Bash(*DROP *)", "Bash(*drop *)", "Bash(*TRUNCATE *)", "Bash(*truncate *)",
      "Bash(*DELETE FROM*)", "Bash(*delete from*)", "Bash(*ALTER TABLE*)", "Bash(*alter table*)"
    ]
  }
JSON
