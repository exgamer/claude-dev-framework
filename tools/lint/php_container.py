#!/usr/bin/env python3
"""Какой PHP-контейнер брать: у каждого разработчика свои имена, поэтому в фреймворке имя не зашито.

Порядок для проекта: `--container` → `PHP_CONTAINER` → запомненный для этого проекта → единственный запущенный
контейнер, в который смонтирован проект и в котором есть php → спросить (в терминале) и запомнить.
Для стенда фреймворка (проекта нет): `--container` → `PHP_CONTAINER` → запомненный для стенда → единственный запущенный
контейнер с php → спросить и запомнить.
Запомненное — `tools/lint/.php-containers.json` у разработчика (в git не попадает, в проект ничего не пишется).

Командная строка (для `run.sh` и `install.sh`):
  python3 tools/lint/php_container.py stand            — имя контейнера стенда (спросит, если не запомнено)
  python3 tools/lint/php_container.py project <папка>  — имя контейнера проекта (найдёт или спросит)
Код возврата 2 и подсказка в stderr — контейнер не выбран (нет docker, нет запущенных, не терминал).
"""
import json
import os
import subprocess
import sys
from pathlib import Path

STORE = Path(__file__).resolve().parent / ".php-containers.json"
STAND = "<стенд фреймворка>"


def _load():
    try:
        return json.loads(STORE.read_text())
    except (OSError, ValueError):
        return {}


def _save(key, name):
    data = _load()
    data[key] = name
    STORE.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n")


def running():
    try:
        out = subprocess.run(["docker", "ps", "--format", "{{.Names}}"], capture_output=True, text=True, timeout=20)
    except (OSError, subprocess.TimeoutExpired):
        return []
    return out.stdout.split() if out.returncode == 0 else []


def has_php(name):
    try:
        return subprocess.run(["docker", "exec", name, "php", "-v"], capture_output=True, timeout=20).returncode == 0
    except subprocess.TimeoutExpired:
        return False


def mounts_project(name, project: Path):
    try:
        mounts = json.loads(subprocess.check_output(["docker", "inspect", name, "--format", "{{json .Mounts}}"], timeout=20))
    except (OSError, subprocess.CalledProcessError, subprocess.TimeoutExpired, ValueError):
        return False
    for m in mounts:
        src = Path(m.get("Source", ""))
        if src != Path("/") and (project == src or src in project.parents):
            return True
    return False


def ask(what, candidates):
    if not sys.stdin.isatty() or not candidates:
        return None
    print(f"PHP-контейнер для {what} — запущенные контейнеры с php:", file=sys.stderr)
    for i, c in enumerate(candidates, 1):
        print(f"  {i}. {c}", file=sys.stderr)
    answer = input("номер или имя (пусто — отмена): ").strip()
    if answer.isdigit() and 1 <= int(answer) <= len(candidates):
        return candidates[int(answer) - 1]
    return answer if answer in candidates else None


def resolve(project: Path | None = None, explicit: str | None = None, interactive=True):
    """Имя контейнера или None (тогда — hint())."""
    if explicit:
        return explicit
    if os.environ.get("PHP_CONTAINER"):
        return os.environ["PHP_CONTAINER"]
    if project and not project.is_dir():
        return None
    key = str(project.resolve()) if project else STAND
    saved = _load().get(key)
    names = running()
    if saved and saved in names:
        return saved
    with_php = [n for n in names if has_php(n)]
    if project:
        mounted = [n for n in with_php if mounts_project(n, project.resolve())]
        if len(mounted) == 1:
            _save(key, mounted[0])
            return mounted[0]
        with_php = mounted or with_php
    if not project and len(with_php) == 1:  # стенд: единственный запущенный контейнер с php — спрашивать не о чем
        _save(key, with_php[0])
        return with_php[0]
    choice = ask(f"проекта {project}" if project else "стенда фреймворка", with_php) if interactive else None
    if choice:
        _save(key, choice)
    return choice


def hint(project: Path | None = None):
    if project and not project.is_dir():
        return f"папки проекта {project} нет"
    what = f"проекта {project}" if project else "стенда фреймворка"
    return (f"PHP-контейнер для {what} не выбран: запустите контейнер с php и повторите в терминале (спросит и запомнит), "
            f"или --container <имя> / PHP_CONTAINER=<имя>")


def main():
    if len(sys.argv) < 2 or sys.argv[1] not in ("stand", "project") or (sys.argv[1] == "project" and len(sys.argv) < 3):
        print(__doc__, file=sys.stderr)
        return 2
    project = Path(sys.argv[2]) if sys.argv[1] == "project" else None
    name = resolve(project)
    if not name:
        print(hint(project), file=sys.stderr)
        return 2
    print(name)
    return 0


if __name__ == "__main__":
    sys.exit(main())
