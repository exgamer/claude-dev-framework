#!/usr/bin/env python3
"""Доказательство для `tools/check/lint-rules.py`: ловит потерю правила, случая, тега и места запуска.

Запуск из корня репозитория: python3 tools/check/lint-proof/rules.py — работает на временной копии.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
CASES = [  # (имя, файл, было, стало, что должно быть в выводе)
    ("правило конфига без строки таблицы", "stacks/go/examples/tariff/.golangci.yml", "      rules:\n        domain-no-infra:",
     "      rules:\n        zz-extra-rule:\n          files:\n            - \"**/zz/**\"\n          deny:\n            - pkg: \"zz\"\n        domain-no-infra:", "«zz-extra-rule» без строки"),
    ("строка таблицы без правила в конфиге", "tools/check/lint-proof/RULES.md", "| nlreturn |", "| zz-rule |", "правила «zz-rule» нет в конфиге"),
    ("случай доказательства потерян", "tools/check/lint-proof/go.py", '("ctx не первым",', '("zz ctx",', "случая «ctx не первым» нет"),
    ("тег не из каталога", "tools/check/lint-proof/RULES.md", "| `initialism-case` |", "| `zz-missing-tag` |", "zz-missing-tag"),
    ("пустой «Остаток — агенту»", "tools/check/lint-proof/RULES.md", "| пустые строки между логическими блоками, builder по вызову на строку |", "|  |", "пустая колонка"),
    ("место запуска не вызывает прогон", "skills/dev-rule/SKILL.md", "golangci-lint run", "golangci-lint-zz", "не вызывает прогон Go"),
]


def copy_repo(dst):
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"], cwd=ROOT).decode().split()
    for f in files:
        if f.startswith("tools/graph/") or not os.path.isfile(os.path.join(ROOT, f)):
            continue
        os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
        shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))


def run(dst):
    return subprocess.run([sys.executable, "tools/check/lint-rules.py"], cwd=dst, capture_output=True, text=True).stdout


def main():
    fails = 0
    for name, path, old, new, expect in CASES:
        work = tempfile.mkdtemp(prefix="rulesproof-")
        copy_repo(work)
        p = os.path.join(work, path)
        text = open(p, encoding="utf-8").read()
        assert old in text, f"{name}: нет «{old}» в {path}"
        open(p, "w", encoding="utf-8").write(text.replace(old, new, 1))
        ok = expect in run(work)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(work)
    work = tempfile.mkdtemp(prefix="rulesproof-")
    copy_repo(work)
    clean = run(work).split("\n")[0]
    print("чистый репозиторий:", clean)
    fails += not clean.endswith("находок: 0")
    shutil.rmtree(work)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
