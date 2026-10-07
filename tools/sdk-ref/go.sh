#!/usr/bin/env bash
# Справочник Go SDK из исходников в module cache.
#   ./go.sh <out-dir> --gomod path/to/go.mod     — версии из go.mod проекта (те, что реально используются)
#   ./go.sh <out-dir> --latest [module-prefix]   — самые свежие версии из кэша (по умолчанию git.mpinnovations.kz/mps/go-packages)
# Пакеты должны быть в кэше (go mod download в проекте). Сеть не используется.
set -euo pipefail

OUT="$1"; MODE="$2"; ARG="${3:-git.mpinnovations.kz/mps/go-packages}"
HERE="$(cd "$(dirname "$0")" && pwd)"
CACHE="$(go env GOMODCACHE)"
mkdir -p "$OUT"

dirs=()
if [ "$MODE" = "--gomod" ]; then
  while read -r mod ver; do
    d="$CACHE/$mod@$ver"
    [ -d "$d" ] && dirs+=("$d") || echo "нет в кэше: $mod@$ver" >&2
  done < <(grep -E '^\s*[^ ]*/gosdk-[a-z-]+ v' "$ARG" | grep -v gosdk-generator | awk '{print $1, $2}')
else
  for m in $(ls "$CACHE/$ARG" | sed 's/@.*//' | sort -u | grep -v gosdk-generator); do
    dirs+=("$(ls -d "$CACHE/$ARG/$m"@* | sort -V | tail -1)")
  done
fi

for d in "${dirs[@]}"; do
  name="$(basename "$d" | sed 's/@.*//')"
  (cd "$HERE/goref" && GOWORK=off GOFLAGS= go run . "$d") > "$OUT/$name.md"
  echo "$OUT/$name.md  ← $(basename "$d")"
done
