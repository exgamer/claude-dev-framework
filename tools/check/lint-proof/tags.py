#!/usr/bin/env python3
"""Доказательство для `tools/check/tags.py`: каждое правило ловит заведомое нарушение, чистый репозиторий — 0 находок.

Запуск из корня репозитория: python3 tools/check/lint-proof/tags.py
Работает на временной копии нужных файлов; репозиторий не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
CASES = [  # (имя, файл, что дописать в конец, что должно быть в выводе)
    ("тег не из каталога", "stacks/go/conventions.md", "\n- Пример (тег `zz-unknown-tag`).\n", "zz-unknown-tag"),
    ("два уровня у тега", "stacks/go/review-tags.md", "\n| `layer-dependency-forbidden` | [ИНФО] | дубль | — |\n", "два уровня у тега `layer-dependency-forbidden`"),
    ("пометка памятки ≠ каталог", "stacks/go/checklist.md", "\n99. **И** Пример (`layer-dependency-forbidden`).\n", "пометка **И**"),
]


def copy_repo(dst):
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard", "*.md"], cwd=ROOT).decode().split()
    for f in files:
        if f.startswith("tools/graph/") or not os.path.isfile(os.path.join(ROOT, f)):
            continue
        os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
        shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
    subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
    subprocess.run(["git", "add", "-A"], cwd=dst, check=True)


def run(dst):
    return subprocess.run([sys.executable, os.path.join(ROOT, "tools/check/tags.py")], cwd=dst, capture_output=True, text=True).stdout


def main():
    fails = 0
    for name, path, add, expect in CASES:
        work = tempfile.mkdtemp(prefix="tagsproof-")
        copy_repo(work)
        with open(os.path.join(work, path), "a", encoding="utf-8") as f:
            f.write(add)
        ok = expect in run(work)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(work)
    work = tempfile.mkdtemp(prefix="tagsproof-")
    copy_repo(work)
    clean = run(work).split("\n")[0]
    print("чистый репозиторий:", clean)
    fails += not clean.endswith("находок: 0")
    shutil.rmtree(work)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
