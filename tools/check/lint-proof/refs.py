#!/usr/bin/env python3
"""Доказательство для `tools/check/refs.py`: ловит ссылку на несуществующий пункт и раздел, не ловит существующие.

Запуск из корня репозитория: python3 tools/check/lint-proof/refs.py — на временной копии, репозиторий не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
CASES = [  # (имя, строка в новом файле, ожидается находка)
    ("нет пункта", "См. `stacks/go/checklist.md` п. 999.", "нет п. 999"),
    ("нет раздела", "См. `core/principles.md`, «Раздела такого нет».", "нет раздела или фразы"),
    ("голое имя по стеку документа", "См. `conventions.md` п. 999.", "нет п. 999"),
    ("голое имя не из stacks/", "См. `review.md` п. 99.", "нет п. 99"),
    ("нет раздела по голому имени", "См. `legacy-refactor.md`, «Раздела такого нет».", "нет раздела или фразы"),
    ("метка стека перед ссылкой", "PHP — `conventions.md` п. 13; Go — `conventions.md` п. 999.", "stacks/go/conventions.md нет п. 999"),
    ("нет шага", "См. `{DF}/skills/dev-review/SKILL.md`, Шаг 9.", "нет «Шаг 9»"),
    ("разрешено: шаг есть", "См. `{DF}/skills/dev-security/SKILL.md`, Шаг 2a.", None),
    ("разрешено: пункт есть", "См. `stacks/go/checklist.md` п. 3.", None),
    ("разрешено: раздел есть", "См. `core/principles.md`, «Пробелы и споры».", None),
]


def main():
    fails = 0
    for name, text, expect in CASES:
        dst = tempfile.mkdtemp(prefix="refsproof-")
        files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"], cwd=ROOT).decode().split()
        for f in files:
            if os.path.isfile(os.path.join(ROOT, f)):
                os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
                shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
        with open(os.path.join(dst, "stacks/go/zz-proof.md"), "w", encoding="utf-8") as f:
            f.write(f"# zz\n\n{text}\n")
        subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
        subprocess.run(["git", "add", "-A"], cwd=dst, check=True)
        res = subprocess.run([sys.executable, "tools/check/refs.py"], cwd=dst, capture_output=True, text=True)
        hit = "stacks/go/zz-proof.md" in res.stdout and (expect is None or expect in res.stdout)
        ok = hit if expect else "stacks/go/zz-proof.md" not in res.stdout
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
