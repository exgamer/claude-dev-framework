#!/usr/bin/env python3
"""Формат журнала решений и бэклога: строки читаются ролями и скриптами по колонкам — сломанная строка теряется молча.

Запуск из корня репозитория: python3 tools/check/tables.py
Код возврата: 0 — чисто, 1 — есть находки.

- `core/decisions-log.md`: каждая строка таблицы — 3 непустые колонки, дата `ГГГГ-ММ-ДД` (порядок дат не проверяется:
  ранние записи вносились не по порядку);
- `core/backlog.md`: 6 непустых колонок (формат — в шапке файла), ID `B-<номер>`/`D-<номер>` без повторов,
  уровень — `[ОШИБКА]`/`[ВНИМАНИЕ]`/`[ИНФО]`.
Колонки делятся по « | » — так же, как их читают остальные проверки; `|` внутри ячейки — без пробелов вокруг.
"""
import re
import sys

LEVELS = {"[ОШИБКА]", "[ВНИМАНИЕ]", "[ИНФО]"}


def rows(path):
    for n, line in enumerate(open(path, encoding="utf-8"), 1):
        line = line.rstrip("\n")
        if line.startswith("|") and not re.fullmatch(r"\|(-+\|)+", line):
            yield n, [c.strip() for c in line.strip().strip("|").split(" | ")]


def main():
    problems = []
    log = "core/decisions-log.md"
    for n, cells in rows(log):
        if cells[0] == "Дата":
            continue
        if len(cells) != 3 or not all(cells):
            problems.append(f"{log}:{n}: {len(cells)} колонок или пустая ячейка (нужно 3: дата | решение | где записано)")
        elif not re.fullmatch(r"\d{4}-\d{2}-\d{2}", cells[0]):
            problems.append(f"{log}:{n}: дата «{cells[0]}» не в формате ГГГГ-ММ-ДД")
    backlog = "core/backlog.md"
    seen = {}
    for n, cells in rows(backlog):
        if cells[0] == "ID":
            continue
        if len(cells) != 6 or not all(cells):
            problems.append(f"{backlog}:{n}: {len(cells)} колонок или пустая ячейка (нужно 6, формат — в шапке файла)")
            continue
        if not re.fullmatch(r"[BD]-\d+", cells[0]):
            problems.append(f"{backlog}:{n}: ID «{cells[0]}» не B-<номер>/D-<номер>")
        elif cells[0] in seen:
            problems.append(f"{backlog}:{n}: ID {cells[0]} уже есть в строке {seen[cells[0]]}")
        seen.setdefault(cells[0], n)
        if cells[1] not in LEVELS:
            problems.append(f"{backlog}:{n}: уровень «{cells[1]}» не из [ОШИБКА]/[ВНИМАНИЕ]/[ИНФО]")
    print(f"журнал и бэклог: находок: {len(problems)}")
    for p in problems:
        print(f"  {p}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
