#!/usr/bin/env bash
# Справочник mps/core и mps/utils из vendor проекта (версии — из composer.lock проекта).
#   ./php.sh <out-dir> <project-dir> [package ...]   (по умолчанию: mps/core mps/utils)
set -euo pipefail

OUT="$1"; PROJECT="$2"; shift 2
PACKAGES=("${@:-mps/core mps/utils}")
HERE="$(cd "$(dirname "$0")" && pwd)"
mkdir -p "$OUT"

for pkg in ${PACKAGES[@]}; do
  dir="$PROJECT/vendor/$pkg"
  [ -d "$dir" ] || { echo "нет $dir (composer install?)" >&2; continue; }
  version="$(python3 -I -c 'import json,sys; print(next((p["version"] for p in json.load(open(sys.argv[1]))["packages"] if p["name"]==sys.argv[2]), ""))' "$PROJECT/composer.lock" "$pkg")"
  name="$(echo "$pkg" | tr '/' '-')-$version"
  PHPREF_VERSION="$version" python3 -I "$HERE/phpref.py" "$dir" > "$OUT/$name.md"
  echo "$OUT/$name.md  ← $pkg $version"
done
