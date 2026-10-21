#!/usr/bin/env python3
"""Прогон анализаторов фреймворка на реальных проектах разработчика — до принятия правки анализаторов.

Эталоны чисты по построению: шум новой проверки (ложные находки на чужом коде, монорепозиторий, старые строки
изменённого файла) видит только реальный проект. Правило — `skills/dev-rule/SKILL.md`, Шаг 3а.

Список проектов — `tools/lint/.real-projects.json` у разработчика (в git не попадает, в фреймворке путей нет).
Запись: стек (`go`/`php`), путь, подпроект (`--sub`, PHP), контейнер (необязательно — иначе `php_container.py`).

  python3 tools/lint/real_projects.py list
  python3 tools/lint/real_projects.py add <go|php> <путь> [--sub X] [--container Y]
  python3 tools/lint/real_projects.py run [--range N] [--out <каталог>]

`run` — по каждому проекту `tools/lint/lint.py` дважды: полный прогон и `--base` = ревизия N коммитов назад
(по умолчанию 20) по пути подпроекта, без merge. Только чтение: `git status` и дифф рабочего дерева проекта
сравниваются до и после — изменился, ошибка. Вывод — число находок по правилу/тегу на проект и режим
(golangci — по линтеру, revive — по правилу; gocheck — по правилу; phpstan — по identifier; routes — по тегу;
silence — по виду) и путь полного вывода (`--out`, по умолчанию — временный каталог).
Списка нет или пуст: в терминале — спросит пути и запомнит; не в терминале — код 2 с подсказкой `add`.
Коды: 0 — все прогоны дошли до конца, проекты не изменились; 1 — нет; 2 — нет списка или неверные аргументы.
"""
import argparse
import hashlib
import json
import re
import subprocess
import sys
import tempfile
from collections import Counter
from pathlib import Path

HERE = Path(__file__).resolve().parent
STORE = HERE / ".real-projects.json"
LINT = HERE / "lint.py"


def load() -> list[dict]:
    try:
        data = json.loads(STORE.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return []
    return [p for p in data if isinstance(p, dict) and p.get("stack") in ("go", "php") and p.get("path")] \
        if isinstance(data, list) else []


def save(projects: list[dict]):
    STORE.write_text(json.dumps(projects, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def entry(stack, path, sub=None, container=None) -> dict | str:
    """Запись списка или текст ошибки."""
    root = Path(path).expanduser().resolve()
    if stack not in ("go", "php"):
        return f"стек {stack!r}: go или php"
    if not root.is_dir():
        return f"папки {root} нет"
    if subprocess.run(["git", "rev-parse", "--git-dir"], cwd=root, capture_output=True).returncode:
        return f"{root} — не репозиторий git (нужен для --base)"
    if stack == "php" and not sub:
        return "php: нужен --sub <каталог подпроекта> (psr-4 из composer.json проекта)"
    if sub and not (root / sub).is_dir():
        return f"подпроекта {root / sub} нет"
    e = {"stack": stack, "path": str(root)}
    if sub:
        e["sub"] = sub
    if container:
        e["container"] = container
    return e


def remember(projects: list[dict], e: dict) -> list[dict]:
    rest = [p for p in projects if (p["path"], p.get("sub")) != (e["path"], e.get("sub"))]
    return rest + [e]


def ask() -> list[dict]:
    """Спросить проекты в терминале; пустой стек — конец."""
    print("Реальные проекты для прогона анализаторов (список — tools/lint/.real-projects.json, в git не попадает).",
          file=sys.stderr)
    projects = []
    while True:
        stack = input("стек (go/php, пусто — закончить): ").strip()
        if not stack:
            return projects
        path = input("путь к проекту: ").strip()
        sub = (input("подпроект (--sub): ").strip() or None) if stack == "php" else None
        container = (input("PHP-контейнер (пусто — найдёт или спросит lint.py): ").strip() or None) if stack == "php" else None
        e = entry(stack, path, sub, container)
        if isinstance(e, str):
            print(e, file=sys.stderr)
            continue
        projects = remember(projects, e)


def label(p: dict) -> str:
    return f"{p['stack']} {p['path']}" + (f" --sub {p['sub']}" if p.get("sub") else "") + \
        (f" --container {p['container']}" if p.get("container") else "")


def git(root: Path, *args) -> subprocess.CompletedProcess:
    return subprocess.run(["git", *args], cwd=root, capture_output=True, text=True)


def snapshot(root: Path) -> str:
    """Состояние проекта: HEAD, git status (с неотслеживаемыми) и хеш диффа рабочего дерева."""
    head = git(root, "rev-parse", "HEAD").stdout.strip()
    status = git(root, "status", "--porcelain=v1", "--untracked-files=all").stdout
    diff = subprocess.run(["git", "diff", "HEAD", "--binary"], cwd=root, capture_output=True).stdout
    return f"{head}\n{hashlib.sha1(diff).hexdigest()}\n{status}"


def base_rev(root: Path, sub, n: int) -> str | None:
    """Ревизия до N-го с конца коммита без merge, задевшего подпроект (или проект)."""
    revs = git(root, "rev-list", "--no-merges", "-n", str(n), "HEAD", "--", sub or ".").stdout.split()
    if not revs:
        return None
    parent = git(root, "rev-parse", "--verify", "--quiet", revs[-1] + "^")
    return parent.stdout.strip() if parent.returncode == 0 else revs[-1]


GO_LINE = re.compile(r"^(\S+\.go):\d+:\d+: (.*) \(([\w-]+)\)$")
REVIVE_RULE = re.compile(r"^([\w-]+): ")
ROUTE_LINE = re.compile(r"^\S+:\d+: (?!\[check-silenced\]).* \(([\w-]+)\)$")
SILENCE_LINE = re.compile(r"^\S+:\d+: \[check-silenced\] (.*)$")
PHPSTAN_ID = re.compile(r"🪪\s+(\S+)")
PHPSTAN_FOUND = re.compile(r"\[ERROR\] Found (\d+) errors?")


def count(stack: str, out: str) -> tuple[Counter, list[str]]:
    """Находки по правилу/тегу и заметки (тесты не компилируются, правило не проверялось)."""
    c, notes = Counter(), []
    in_gocheck = False
    for line in out.splitlines():
        if line.startswith("$ gocheck"):
            in_gocheck = True
            continue
        if m := SILENCE_LINE.match(line):
            c[f"silence: {m.group(1)}"] += 1
            continue
        if stack == "go" and (m := GO_LINE.match(line)):
            msg, tool = m.group(2), m.group(3)
            if in_gocheck:
                c[f"gocheck:{tool}"] += 1
            elif tool == "typecheck" and m.group(1).endswith("_test.go"):
                notes.append("тесты проекта не компилируются — golangci шёл без тестов")
            else:
                rule = REVIVE_RULE.match(msg) if tool == "revive" else None
                c[f"golangci:{tool}" + (f":{rule.group(1)}" if rule else "")] += 1
            continue
        if stack == "php":
            if m := PHPSTAN_ID.search(line):
                c[f"phpstan:{m.group(1)}"] += 1
            elif m := ROUTE_LINE.match(line):
                c[f"routes:{m.group(1)}"] += 1
            elif line.startswith("routes: ") and "находок" not in line:
                notes.append(line)
    if stack == "php" and (m := PHPSTAN_FOUND.search(out)):
        without = int(m.group(1)) - sum(v for k, v in c.items() if k.startswith("phpstan:"))
        if without > 0:
            c["phpstan:(без identifier — см. вывод)"] += without
    return c, sorted(set(notes))


def run_one(p: dict, base, out_file: Path) -> tuple[Counter, list[str], bool]:
    cmd = [sys.executable, str(LINT), p["stack"], p["path"]]
    if p.get("sub"):
        cmd += ["--sub", p["sub"]]
    if p.get("container"):
        cmd += ["--container", p["container"]]
    if base:
        cmd += ["--base", base]
    r = subprocess.run(cmd, capture_output=True, text=True, stdin=subprocess.DEVNULL)
    text = f"$ {' '.join(cmd)}\n{r.stdout}{r.stderr}"
    out_file.write_text(text, encoding="utf-8")
    finished = "Остаток — проверить глазами" in r.stdout
    c, notes = count(p["stack"], r.stdout)
    return c, notes, finished


def run(projects: list[dict], n: int, out_dir: Path) -> int:
    out_dir.mkdir(parents=True, exist_ok=True)
    code = 0
    for i, p in enumerate(projects, 1):
        root = Path(p["path"])
        print(f"\n== {i}. {label(p)}")
        if not root.is_dir() or git(root, "rev-parse", "--git-dir").returncode:
            print("   ОШИБКА: папки нет или не репозиторий git — пропущен")
            code = 1
            continue
        base = base_rev(root, p.get("sub"), n)
        before = snapshot(root)
        modes = [("полный", None)] + ([(f"--base {base[:12]} ({n} коммитов без merge по {p.get('sub') or 'проекту'})", base)]
                                       if base else [])
        if not base:
            print(f"   --base: нет коммитов по {p.get('sub') or 'проекту'} — режим пропущен")
            code = 1
        for j, (mode, rev) in enumerate(modes):
            out_file = out_dir / f"{i}-{root.name}{'-' + p['sub'] if p.get('sub') else ''}-{'base' if rev else 'full'}.txt"
            c, notes, finished = run_one(p, rev, out_file)
            total = sum(c.values())
            state = "" if finished else " — НЕ ДОШЁЛ ДО КОНЦА"
            print(f"-- {mode}: находок {total}{state}; вывод: {out_file}")
            for k, v in sorted(c.items()):
                print(f"   {v:5d}  {k}")
            for note in notes:
                print(f"   заметка: {note}")
            if not finished:
                code = 1
        if snapshot(root) != before:
            print("   ОШИБКА: git status или рабочее дерево проекта изменились после прогона — прогон должен только читать")
            code = 1
        else:
            print("   проект не изменён (git status и дифф рабочего дерева до/после совпадают)")
    return code


def main() -> int:
    ap = argparse.ArgumentParser(description="Прогон анализаторов фреймворка на реальных проектах (список — локальный, вне git)")
    sp = ap.add_subparsers(dest="cmd", required=True)
    sp.add_parser("list", help="показать список")
    a = sp.add_parser("add", help="добавить или заменить проект")
    a.add_argument("stack", choices=["go", "php"])
    a.add_argument("path")
    a.add_argument("--sub", help="php: каталог подпроекта (psr-4 из composer.json проекта)")
    a.add_argument("--container", help="php: контейнер с PHP проекта (иначе — найдёт или спросит lint.py)")
    r = sp.add_parser("run", help="полный прогон и --base по каждому проекту")
    r.add_argument("--range", type=int, default=20, help="--base: N коммитов без merge по пути подпроекта (по умолчанию 20)")
    r.add_argument("--out", help="каталог полного вывода (по умолчанию — временный)")
    args = ap.parse_args()

    projects = load()
    if args.cmd == "add":
        e = entry(args.stack, args.path, args.sub, args.container)
        if isinstance(e, str):
            print(e, file=sys.stderr)
            return 2
        save(remember(projects, e))
        print(f"запомнено: {label(e)}")
        return 0
    if not projects:
        if not sys.stdin.isatty():
            print(f"список реальных проектов пуст ({STORE.name}): python3 {Path(__file__).relative_to(HERE.parent.parent)} "
                  "add <go|php> <путь> [--sub X] [--container Y]", file=sys.stderr)
            return 2
        projects = ask()
        if not projects:
            return 2
        save(projects)
    if args.cmd == "list":
        for p in projects:
            print(label(p))
        return 0
    if args.range < 1:
        print("--range: N ≥ 1", file=sys.stderr)
        return 2
    out_dir = Path(args.out).expanduser().resolve() if args.out else Path(tempfile.mkdtemp(prefix="df-real-projects-"))
    return run(projects, args.range, out_dir)


if __name__ == "__main__":
    sys.exit(main())
