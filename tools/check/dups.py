#!/usr/bin/env python3
"""Кандидаты в дубли: похожие абзацы в разных файлах правил — одно правило в двух местах расходится при правке
(«одно место + ссылки», O-23). Это кандидаты для глаз, не ошибки: код возврата всегда 0.

Запуск из корня репозитория: python3 tools/check/dups.py [--min 0.5]

Похожесть — доля общих троек слов (Жаккар) у абзацев от 25 слов, блоки кода не считаются. Не кандидаты:
- шаблон — шапка файла (первые абзацы), дословно повторённая в 3+ файлах одной папки (шапка справочника); тот же
  абзац в теле трёх ролей, в т. ч. трёх агентов одной папки, — кандидат;
- зеркало стеков — пара файлов, одинаковых после замены go ↔ php (`dev-go`/`dev-php`, `df-go`/`df-php`,
  `stacks/go/X`/`stacks/php/X`): роль читает только свой стек, повтор намеренный;
- отсылка — абзац короче 40 слов со ссылкой «`файл.md`, «Раздел»»: это и есть «одно место + ссылки».
Не проверяются: `core/decisions-log.md`, `core/backlog.md`, справочники `official/`/`reference/`, `tools/graph/`.
"""
import argparse
import collections
import itertools
import os
import re
import sys

sys.path.insert(0, os.path.dirname(__file__))
import links  # noqa: E402

SKIP = ("core/decisions-log.md", "CHANGELOG.md", "core/backlog.md", "/official/", "/reference/", "tools/graph/")
MIN_WORDS = 25
POINTER_WORDS = 40


def mirror(a, b):
    swap = lambda p: p.replace("go", "\0").replace("php", "go").replace("\0", "php")  # noqa: E731
    return a != b and swap(a) == b


def paragraphs(doc):
    text = re.sub(r"```.*?```", "", open(doc, encoding="utf-8", errors="ignore").read(), flags=re.S)
    for idx, p in enumerate(re.split(r"\n\s*\n|\n(?=[-|#]|\d+\. )", text)):
        words = re.findall(r"\w+", p.lower())
        pointer = len(words) < POINTER_WORDS and re.search(r"`[^`]+\.md`,\s*«", p)
        if len(words) >= MIN_WORDS and not pointer:
            yield p.strip().replace("\n", " "), frozenset(zip(words, words[1:], words[2:])), idx < 3


def main():
    ap = argparse.ArgumentParser(description="Кандидаты в дубли — похожие абзацы в разных файлах")
    ap.add_argument("--min", type=float, default=0.5, help="порог похожести 0..1 (по умолчанию 0.5)")
    a = ap.parse_args()
    raw = [(d, t, s, head) for d in links.repo_files() if d.endswith(".md") and not any(x in d for x in SKIP)
           for t, s, head in paragraphs(d)]
    files_of = collections.defaultdict(set)
    for d, _, s, head in raw:
        if head:
            files_of[s].add(d)
    template = {s for s, fs in files_of.items()
                if max(collections.Counter(os.path.dirname(x) for x in fs).values()) >= 3}
    paras = [(d, t, s) for d, t, s, _ in raw]
    parent = list(range(len(paras)))  # похожие абзацы — в одну группу: один повтор в шести ролях — одна строка

    def find(i):
        while parent[i] != i:
            parent[i] = parent[parent[i]]
            i = parent[i]
        return i

    best = {}
    for i, k in itertools.combinations(range(len(paras)), 2):
        (f1, _, s1), (f2, _, s2) = paras[i], paras[k]
        if f1 == f2 or s1 in template or s2 in template or mirror(f1, f2):
            continue
        j = len(s1 & s2) / len(s1 | s2)
        if j >= a.min:
            parent[find(i)] = find(k)
            best[i], best[k] = max(best.get(i, 0), j), max(best.get(k, 0), j)
    groups = collections.defaultdict(list)
    for i in best:
        groups[find(i)].append(i)
    out = sorted(groups.values(), key=lambda g: (-len({paras[i][0] for i in g}), -max(best[i] for i in g)))
    print(f"абзацев: {len(paras)}; групп кандидатов в дубли (похожесть ≥ {a.min}): {len(out)}")
    for g in out:
        files = sorted({paras[i][0] for i in g})
        print(f"  {len(files)} файлов, до {max(best[i] for i in g):.2f}: «{paras[g[0]][1][:90]}»")
        for x in files:
            print(f"      {x}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
