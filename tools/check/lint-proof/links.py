#!/usr/bin/env python3
"""Доказательство для `tools/check/links.py`: ловит битую ссылку и орфан (в т. ч. файл, на который ссылается
только журнал); голое имя из роли — не орфан; чистая копия — 0.

Запуск из корня репозитория: python3 tools/check/lint-proof/links.py — на временной копии (свой git), репозиторий не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
NEW = "approaches/process/zz-proof.md"


def copy_repo():
    dst = tempfile.mkdtemp(prefix="linksproof-")
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"], cwd=ROOT).decode().split()
    for f in files:
        if f.startswith(("tools/graph/graphify-out/", "tools/graph/cache/", "tools/graph/corpus/")) or not os.path.isfile(os.path.join(ROOT, f)):
            continue
        os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
        shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
    subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
    return dst


def write(dst, path, text):
    with open(os.path.join(dst, path), "w", encoding="utf-8") as f:
        f.write(text)


def append(dst, path, text):
    with open(os.path.join(dst, path), "a", encoding="utf-8") as f:
        f.write(text)


def new_file_ref_from(path, ref):
    return lambda d: write(d, NEW, "# zz\n") or append(d, path, f"\n{ref}\n")


CASES = [  # (имя, правка, что должно быть в выводе, ожидаемый код возврата)
    ("битая ссылка", lambda d: append(d, "skills/dev-qa/SKILL.md", "\nПрочитай `{DF}/approaches/process/zz-missing.md`.\n"),
     "zz-missing.md", 1),
    ("орфан: на файл не ссылается никто", lambda d: write(d, NEW, "# zz\n"), "  " + NEW, 1),
    ("орфан: на файл ссылается только бэклог", new_file_ref_from("core/backlog.md", f"См. `{NEW}`."), "  " + NEW, 1),
    ("орфан: на файл ссылается только CHANGELOG", new_file_ref_from("CHANGELOG.md", f"См. `{NEW}`."), "  " + NEW, 1),
    ("разрешено: роль ссылается голым именем", new_file_ref_from("skills/dev-qa/SKILL.md", "Прочитай `zz-proof.md`."), "орфаны: 0", 0),
    ("разрешено: роль ссылается полным путём", new_file_ref_from("skills/dev-qa/SKILL.md", f"Прочитай `{{DF}}/{NEW}`."), "орфаны: 0", 0),
]


def run(dst):
    return subprocess.run([sys.executable, "tools/check/links.py"], cwd=dst, capture_output=True, text=True)


def main():
    fails = 0
    for name, change, expect, code in CASES:
        dst = copy_repo()
        change(dst)
        res = run(dst)
        ok = expect in res.stdout and res.returncode == code
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(dst)
    dst = copy_repo()
    res = run(dst)
    print("чистая копия:", " / ".join(res.stdout.strip().splitlines()[-2:]))
    fails += res.returncode != 0
    shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
