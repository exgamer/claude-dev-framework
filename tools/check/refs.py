#!/usr/bin/env python3
"""Ссылки на пункты и разделы: `файл.md` п. N и `файл.md`, «Раздел» ведут на то, что в файле есть.

Запуск из корня репозитория: python3 tools/check/refs.py
Код возврата: 0 — чисто, 1 — есть находки.

Проверяются только явные ссылки с файлом рядом — без файла «п. N» может значить пункт любого документа:
- `путь.md` п. 12 / п. 12a / пп. 3, 4 — в файле есть пункт, строка которого начинается с номера («12. », «12a. »);
- `путь.md`, Шаг N / Шаги 1–3 — в файле есть заголовок «Шаг N»;
- `путь.md`, «Название» (сразу после файла, через запятую) — в файле есть такой заголовок или эта фраза дословно
  (раздел могли назвать фразой из него — тогда сверяется наличие фразы).
Голое имя файла, которое есть в обоих стеках (`conventions.md`), — по стеку документа или «PHP —»/«Go —» перед ссылкой,
иначе не проверяется. Не проверяются: `core/decisions-log.md` (история), справочники `official/`/`reference/`, файлы `tools/graph/`.
"""
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
import links  # noqa: E402

SKIP = ("core/decisions-log.md", "/official/", "/reference/", "tools/graph/")
FILE = r"`((?:\{DF\}/)?[\w./{}-]+\.md)`"
ITEM_RE = re.compile(FILE + r"[,]?\s+пп?\.\s*((?:\d+[a-z]?(?:\s*(?:,|и|–|-)\s*)?)+)")
SECTION_RE = re.compile(FILE + r",\s*«([^»]{3,80})»")
STEP_RE = re.compile(FILE + r",?\s+[Шш]аги?\s+((?:\d+[a-zа-я]?(?:\s*(?:,|и|–|-)\s*)?)+)")


def items_of(text):
    return set(re.findall(r"^\s*(\d+[a-z]?)\.\s", text, re.M))


def target_of(doc, token, line, start, files, known):
    """Файл ссылки: путь, голое имя или хвост пути; одно имя в нескольких стеках — по метке «PHP»/«Go» перед ссылкой
    или стеку документа; иначе неоднозначно — не проверяется."""
    return links.resolve_loose(doc, token, known, line, start)


def steps_of(text):
    return set(re.findall(r"^#+\s*Шаг\s+(\d+[a-zа-я]?)\b", text, re.M))


def normalize(s):
    return re.sub(r"[`*_]", "", s).lower()


def main():
    files = links.repo_files()
    known = set(files)
    problems = []
    cache = {}
    checked = 0
    for doc in [f for f in files if f.endswith(".md") and not any(s in f for s in SKIP)]:
        text = open(doc, encoding="utf-8", errors="ignore").read()
        for n, line in enumerate(text.split("\n"), 1):
            for m in ITEM_RE.finditer(line):
                target = target_of(doc, m.group(1), line, m.start(), files, known)
                if not target:
                    continue
                if target not in cache:
                    cache[target] = open(target, encoding="utf-8", errors="ignore").read()
                have = items_of(cache[target])
                checked += 1
                for num in re.findall(r"\d+[a-z]?", m.group(2)):
                    if num not in have:
                        problems.append(f"{doc}:{n}: в {target} нет п. {num}")
            for m in STEP_RE.finditer(line):
                target = target_of(doc, m.group(1), line, m.start(), files, known)
                if not target:
                    continue
                if target not in cache:
                    cache[target] = open(target, encoding="utf-8", errors="ignore").read()
                checked += 1
                have = steps_of(cache[target])
                for num in re.findall(r"\d+[a-zа-я]?", m.group(2)):
                    if num not in have:
                        problems.append(f"{doc}:{n}: в {target} нет «Шаг {num}»")
            for m in SECTION_RE.finditer(line):
                target = target_of(doc, m.group(1), line, m.start(), files, known)
                if not target:
                    continue
                if target not in cache:
                    cache[target] = open(target, encoding="utf-8", errors="ignore").read()
                checked += 1
                if normalize(m.group(2)) not in normalize(cache[target]):
                    problems.append(f"{doc}:{n}: в {target} нет раздела или фразы «{m.group(2)}»")
    print(f"ссылок на пункты и разделы: {checked}; находок: {len(problems)}")
    for p in problems:
        print(f"  {p}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
