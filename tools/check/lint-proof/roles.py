#!/usr/bin/env python3
"""Доказательство для `tools/check/roles.py`: ловит потерю файла ролью, перенос глубже и удалённую роль; чисто — 0.

Запуск из корня репозитория: python3 tools/check/lint-proof/roles.py — на временной копии (свой git), репозиторий не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
GIT = ["git", "-c", "user.email=proof@local", "-c", "user.name=proof"]


def copy_repo():
    dst = tempfile.mkdtemp(prefix="rolesproof-")
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"], cwd=ROOT).decode().split()
    for f in files:
        if f.startswith("tools/graph/") or not os.path.isfile(os.path.join(ROOT, f)):
            continue
        os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
        shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
    # файл, который читает только одна роль: его потерю ничто не прикроет
    with open(os.path.join(dst, "approaches/process/zz-only-qa.md"), "w", encoding="utf-8") as f:
        f.write("# zz\n")
    with open(os.path.join(dst, "skills/dev-qa/SKILL.md"), "a", encoding="utf-8") as f:
        f.write("\nПрочитай `{DF}/approaches/process/zz-only-qa.md`.\n")
    subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
    subprocess.run(["git", "add", "-A"], cwd=dst, check=True)
    subprocess.run(GIT + ["commit", "-qm", "base"], cwd=dst, check=True)
    return dst


def run(dst):
    return subprocess.run([sys.executable, "tools/check/roles.py"], cwd=dst, capture_output=True, text=True)


def edit(dst, path, old, new):
    p = os.path.join(dst, path)
    text = open(p, encoding="utf-8").read()
    assert old in text, f"нет «{old}» в {path}"
    open(p, "w", encoding="utf-8").write(text.replace(old, new))


CASES = [  # (имя, правка, что должно быть в выводе, ожидаемый код возврата)
    ("роль потеряла файл", lambda d: edit(d, "skills/dev-qa/SKILL.md", "Прочитай `{DF}/approaches/process/zz-only-qa.md`.", ""), "ПОТЕРЯН   approaches/process/zz-only-qa.md", 1),
    ("файл ушёл глубже", lambda d: edit(d, "skills/dev-qa/SKILL.md", "`{DF}/approaches/process/testing.md`", "testing"), "ГЛУБЖЕ    approaches/process/testing.md", None),
    ("потеря файла по голому имени", lambda d: edit(d, "skills/dev-qa/SKILL.md", "Прочитай `{DF}/approaches/process/zz-only-qa.md`.", "Прочитай `zz-only-qa.md`.") or edit(d, "skills/dev-qa/SKILL.md", "`zz-only-qa.md`", "zz"), "ПОТЕРЯН   approaches/process/zz-only-qa.md", 1),
    ("разрешено: ссылка по голому имени — не потеря", lambda d: edit(d, "skills/dev-qa/SKILL.md", "Прочитай `{DF}/approaches/process/zz-only-qa.md`.", "Прочитай `zz-only-qa.md`."), "потеряно файлов: 0", 0),
    ("роль удалена", lambda d: os.remove(os.path.join(d, "agents/df-qa.md")), "agents/df-qa.md: роли больше нет", 1),
]


def main():
    fails = 0
    for name, change, expect, code in CASES:
        dst = copy_repo()
        change(dst)
        res = run(dst)
        ok = expect in res.stdout and (code is None or res.returncode == code)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(dst)
    dst = copy_repo()
    res = run(dst)
    clean = res.stdout.strip().splitlines()[-1]
    print("чистая копия:", clean)
    fails += res.returncode != 0
    shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
