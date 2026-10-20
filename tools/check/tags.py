#!/usr/bin/env python3
"""Теги ревью: есть в каталоге, один уровень на тег, пометка О/В/И памятки совпадает с каталогом.

Запуск из корня репозитория: python3 tools/check/tags.py
Код возврата: 0 — чисто, 1 — есть находки.

Каталоги (уровень находки — только в них, O-24): `approaches/process/review-tags.md`, `stacks/*/review-tags.md`,
таблица «Каталог тегов безопасности» в `approaches/process/security.md`. Упоминание тега в правилах — только явное:
«тег `x`» / «теги `x`, `y`» / «(`x`)» сразу после слова «тег»; тег ревью всегда с дефисом — одиночное слово (struct-тег Go `binding`, build-тег
`integration`) тегом ревью не считается. Памятка: сверяется пункт с одной пометкой О/В/И и одним тегом; пункты с несколькими уровнями внутри
(«**О** … **В** …») — не сверяются, их проверяет агент.
"""
import re
import subprocess
import sys

CATALOGS = ["approaches/process/review-tags.md", "stacks/go/review-tags.md", "stacks/php/review-tags.md"]
SECURITY = "approaches/process/security.md"
MEMOS = ["stacks/go/checklist.md", "stacks/php/checklist.md"]
LEVELS = {"[ОШИБКА]": "О", "[ВНИМАНИЕ]": "В", "[ИНФО]": "И", "[БЕЗОПАСНОСТЬ]": "О"}
ROW_RE = re.compile(r"^\|\s*`([a-z0-9][a-z0-9-]+)`\s*\|\s*([^|]*)\|")
MENTION_RE = re.compile(r"тег(?:и|ом|а)?\s+((?:`[a-z0-9]+(?:-[a-z0-9]+)+`(?:\s*(?:,|и|/|или)\s*)?)+)")
TAG_RE = re.compile(r"`([a-z0-9]+(?:-[a-z0-9]+)+)`")
MEMO_RE = re.compile(r"^\d+[a-z]?\.\s+\*\*([ОВИ])\*\*")


def first_level(cell: str):
    """Основной уровень строки каталога — первый в ячейке (дальше — оговорки частных случаев)."""
    found = [(cell.find(k), v) for k, v in LEVELS.items() if k in cell]
    return min(found)[1] if found else None


def catalog():
    tags = {}
    for path in CATALOGS + [SECURITY]:
        text = open(path, encoding="utf-8").read()
        if path == SECURITY:
            start = text.find("## Каталог тегов безопасности")
            text = text[start:text.find("\n## ", start + 1)] if start >= 0 else ""
        for line in text.split("\n"):
            m = ROW_RE.match(line)
            if m:
                tags.setdefault(m.group(1), []).append((path, first_level(m.group(2))))
    return tags


def rule_files():
    out = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard", "*.md"]).decode()
    skip = ("core/decisions-log.md", "core/backlog.md", "/official/", "/reference/", "tools/graph/")
    return [f for f in out.split("\n") if f and not any(s in f for s in skip)]


def main():
    tags = catalog()
    problems = []
    for tag, places in sorted(tags.items()):
        levels = {lvl for _, lvl in places if lvl}
        if len(levels) > 1:
            problems.append(f"два уровня у тега `{tag}`: " + ", ".join(f"{p} {l}" for p, l in places))
    for path in rule_files():
        for n, line in enumerate(open(path, encoding="utf-8").read().split("\n"), 1):
            for group in MENTION_RE.findall(line):
                for tag in TAG_RE.findall(group):
                    if tag not in tags:
                        problems.append(f"{path}:{n}: тег `{tag}` не найден в каталогах")
    for path in MEMOS:
        for n, line in enumerate(open(path, encoding="utf-8").read().split("\n"), 1):
            m = MEMO_RE.match(line)
            if not m or len(re.findall(r"\*\*[ОВИ]\*\*", line)) > 1:
                continue
            own = [t for t in TAG_RE.findall(line) if t in tags]
            if len(own) != 1:
                continue
            want = {lvl for _, lvl in tags[own[0]] if lvl}
            if want and m.group(1) not in want:
                problems.append(f"{path}:{n}: пометка **{m.group(1)}**, а тег `{own[0]}` в каталоге — {'/'.join(sorted(want))}")
    print(f"тегов в каталогах: {len(tags)}; находок: {len(problems)}")
    for p in problems:
        print(f"  {p}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
