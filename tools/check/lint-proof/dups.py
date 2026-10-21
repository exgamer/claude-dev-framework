#!/usr/bin/env python3
"""Доказательство для `tools/check/dups.py`: копия правила в двух ролях находится; шаблон, зеркало стеков и короткая
отсылка — нет. Запуск из корня репозитория: python3 tools/check/lint-proof/dups.py — на временной копии.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
RULE = ("Перед сдачей задачи разработчик обязан прогнать миграции на чистой базе, затем откатить их и прогнать "
        "снова, чтобы убедиться в обратимости каждой миграции и в том, что откат не теряет данные справочников "
        "и не ломает внешние ключи соседних таблиц проекта.")
POINTER = "Перед стартом проверь доступ к БД — `{DF}/approaches/process/db-access.md`, «Проверка при старте»; работу это не останавливает и ничего не меняет."
CASES = [  # (имя, {файл: дописываемый абзац}, должна найтись группа с этим текстом)
    ("копия правила в двух ролях", {"skills/dev-qa/SKILL.md": RULE, "skills/dev-analyst/SKILL.md": RULE}, True),
    ("копия правила в трёх агентах одной папки", {"agents/df-qa.md": RULE, "agents/df-analyst.md": RULE, "agents/df-security.md": RULE}, True),
    ("разрешено: зеркало стеков", {"stacks/go/zz.md": RULE, "stacks/php/zz.md": RULE}, False),
    ("разрешено: шаблон в 3 файлах папки", {f"stacks/php/mps-core/zz{i}.md": RULE for i in range(3)}, False),
    ("разрешено: короткая отсылка", {"skills/dev-qa/SKILL.md": POINTER, "skills/dev-analyst/SKILL.md": POINTER}, False),
]


def main():
    fails = 0
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"], cwd=ROOT).decode().split()
    for name, add, expect in CASES:
        dst = tempfile.mkdtemp(prefix="dupsproof-")
        for f in files:
            if os.path.isfile(os.path.join(ROOT, f)) and not f.startswith("tools/graph/"):
                os.makedirs(os.path.dirname(os.path.join(dst, f)) or dst, exist_ok=True)
                shutil.copy(os.path.join(ROOT, f), os.path.join(dst, f))
        for path, text in add.items():
            with open(os.path.join(dst, path), "a", encoding="utf-8") as fh:
                fh.write(f"\n\n{text}\n")
        subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
        subprocess.run(["git", "add", "-A"], cwd=dst, check=True)
        out = subprocess.run([sys.executable, "tools/check/dups.py"], cwd=dst, capture_output=True, text=True).stdout
        found = text[:40] in out
        ok = found == expect
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
