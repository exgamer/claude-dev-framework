#!/usr/bin/env python3
"""Доказательство для `tools/check/tables.py`: ловит сломанные строки журнала и бэклога; чистая копия — 0.

Запуск из корня репозитория: python3 tools/check/lint-proof/tables.py — на временной копии, репозиторий не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
LOG, BACKLOG = "core/decisions-log.md", "core/backlog.md"
CASES = [  # (имя, файл, дописываемая строка, что должно быть в выводе)
    ("лог: 2 колонки", LOG, "| 2026-10-20 | решение без места |", "колонок или пустая ячейка"),
    ("лог: дата не ГГГГ-ММ-ДД", LOG, "| 20.10.2026 | решение | `core/backlog.md` |", "не в формате ГГГГ-ММ-ДД"),
    ("бэклог: повтор ID", BACKLOG, "| D-1 | [ИНФО] | где | суть | что | источник |", "ID D-1 уже есть"),
    ("бэклог: уровень не из каталога", BACKLOG, "| B-999 | [ВАЖНО] | где | суть | что | источник |", "не из [ОШИБКА]"),
    ("бэклог: 5 колонок", BACKLOG, "| B-998 | [ИНФО] | где | суть | источник |", "колонок или пустая ячейка"),
]


def run(dst):
    return subprocess.run([sys.executable, "tools/check/tables.py"], cwd=dst, capture_output=True, text=True)


def copy():
    dst = tempfile.mkdtemp(prefix="tablesproof-")
    os.makedirs(os.path.join(dst, "core"))
    os.makedirs(os.path.join(dst, "tools/check"))
    for f in (LOG, BACKLOG, "tools/check/tables.py"):
        shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
    return dst


def main():
    fails = 0
    for name, path, line, expect in CASES:
        dst = copy()
        with open(os.path.join(dst, path), "a", encoding="utf-8") as f:
            f.write(line + "\n")
        res = run(dst)
        ok = expect in res.stdout and res.returncode == 1
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(dst)
    dst = copy()
    res = run(dst)
    print("чистая копия:", res.stdout.strip().splitlines()[0])
    fails += res.returncode != 0
    shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
