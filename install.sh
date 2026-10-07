#!/usr/bin/env bash
# Установка dev-framework: симлинк фреймворка в ~/.claude/dev-framework и скиллов в ~/.claude/skills/.
# Источник истины — этот репозиторий: правки только здесь, ~/.claude обновляется повторным запуском (симлинки — сразу).
# Ничего не удаляет: если цель уже существует и это не наш симлинк — пропускает и сообщает.
set -euo pipefail

DF_SRC="$(cd "$(dirname "$0")" && pwd)"
CLAUDE_DIR="${HOME}/.claude"
SKILLS_DIR="${CLAUDE_DIR}/skills"

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

mkdir -p "$SKILLS_DIR"
link "$DF_SRC" "${CLAUDE_DIR}/dev-framework"

for skill in "$DF_SRC"/skills/*/; do
  name="$(basename "$skill")"
  link "${skill%/}" "${SKILLS_DIR}/${name}"
done

echo
echo "Триггеры для ~/.claude/CLAUDE.md (добавить вручную, если нужно):"
for skill in "$DF_SRC"/skills/*/; do
  name="$(basename "$skill")"
  echo "When the user types \`/${name}\`, invoke the Skill tool with \`skill: \"${name}\"\` before doing anything else."
done
