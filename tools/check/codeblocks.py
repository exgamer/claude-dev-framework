#!/usr/bin/env python3
"""Блоки кода в правилах синтаксически верны: ```go — `gofmt -e`, ```php — `php -l`. Роль копирует пример как образец,
опечатка в нём становится кодом.

Запуск из корня репозитория: python3 tools/check/codeblocks.py [--php-container <имя>]
Код возврата: 0 — чисто, 1 — есть находки, 2 — проверено не всё: нет gofmt, или php-блоки пропущены (нет php и контейнера).

Блоки — фрагменты, поэтому каждый пробуется как файл, как объявления пакета/класса, как тело функции/метода, как поля
структуры или литерала; не разобрался целиком — по кускам между пустыми строками (куски копятся, пока не разберутся:
тело функции бывает с пустыми строками). Строка `...`, `(...)` и `{ ... }`, `=> ...` — заглушки «здесь остальное», заменяются пустыми;
блок из одних строк `.Метод(...)` — список методов builder-а, не код. Справочники `official/`/`reference/` и
`core/decisions-log.md` не проверяются. Только синтаксис: форматирование и компиляция — нет (у фрагмента нет импортов).
"""
import argparse
import textwrap
import os
import re
import shutil
import subprocess
import sys

sys.path.insert(0, os.path.dirname(__file__))
import links  # noqa: E402

SKIP = ("/official/", "/reference/", "core/decisions-log.md", "tools/graph/")
BLOCK_RE = re.compile(r"^([ \t]*)```(go|php)[ \t]*\n(.*?)^[ \t]*```", re.S | re.M)  # и с отступом (блок в пункте списка)
GO_WRAPS = ["{}", "package x\n{}", "package x\nfunc _() {{\n{}\n}}\n", "package x\ntype _ struct {{\n{}\n}}\n",
            "package x\ntype _ interface {{\n{}\n}}\n", "package x\nvar _ = T{{\n{}\n}}\n"]
PHP_WRAPS = ["{}", "<?php\n{}", "<?php\nclass _X extends _P {{\n{}\n}}\n", "<?php\nfunction _x() {{\n{}\n}}\n",
             "<?php\n$x = [\n{}\n];\n", "<?php\nclass _X extends _P {{\n{}\npublic function _y() {{}}\n}}\n",
             "<?php\n{}\n{{}}\n", "<?php\n{}\nclass _Y {{}}\n", "<?php\ninterface _I {{\n{}\n}}\n"]


class Checker:
    def __init__(self, php):
        self.php = php

    def go_ok(self, src):
        for w in GO_WRAPS:
            if subprocess.run(["gofmt", "-e"], input=w.format(src), capture_output=True, text=True).returncode == 0:
                return True
        return False

    def php_ok(self, src):
        src = src.removeprefix("<?php\n") if src.startswith("<?php") else src
        for w in PHP_WRAPS[1:]:
            if subprocess.run(self.php + ["-l"], input=w.format(src), capture_output=True, text=True).returncode == 0:
                return True
        return False

    def ok(self, lang, src):
        src = re.sub(r"^([ \t]*)(\.\.\.|…)[ \t]*$", r"\1// ...", src, flags=re.M)
        src = re.sub(r"\((\.\.\.|…)\)", "()", src)
        src = re.sub(r"\{\s*(\.\.\.|…)\s*\}", "{}", src)
        src = re.sub(r"=>\s*(\.\.\.|…)", "=> null", src)
        lines = [l for l in src.splitlines() if l.strip() and not l.strip().startswith("//")]
        if lines and all(l.lstrip().startswith(".") for l in lines):
            return True
        check = self.go_ok if lang == "go" else self.php_ok
        if check(src):
            return True
        acc = ""
        for chunk in re.split(r"\n\s*\n", src):
            acc = f"{acc}\n\n{chunk}" if acc else chunk
            if check(acc):
                acc = ""
        return acc == "" or not acc.strip()


def php_cmd(container):
    if shutil.which("php"):
        return ["php"]
    sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), "../lint"))
    import php_container
    container = php_container.resolve(None, container) if shutil.which("docker") else None
    if container and not php_container.has_php(container):  # неверный контейнер — не «все блоки сломаны», а пропуск
        print(f"контейнер {container}: php недоступен — php-блоки не проверяются", file=sys.stderr)
        return None
    return ["docker", "exec", "-i", container, "php"] if container else None


def main():
    p = argparse.ArgumentParser(description="Синтаксис блоков кода go/php в правилах")
    p.add_argument("--php-container", help="контейнер с php, если локального php нет (не задан — контейнер стенда, tools/lint/php_container.py)")
    a = p.parse_args()
    if not shutil.which("gofmt"):
        print("gofmt не найден — проверка не выполнена")
        return 2
    php = php_cmd(a.php_container)
    checker = Checker(php)
    problems, counts = [], {"go": 0, "php": 0}
    for doc in [f for f in links.repo_files() if f.endswith(".md") and not any(s in f for s in SKIP)]:
        text = open(doc, encoding="utf-8", errors="ignore").read()
        for m in BLOCK_RE.finditer(text):
            lang = m.group(2)
            if lang == "php" and not php:
                continue
            counts[lang] += 1
            if not checker.ok(lang, textwrap.dedent(m.group(3))):
                problems.append(f"{doc}:{text[:m.start()].count(chr(10)) + 1}: блок ```{lang} не разбирается")
    skipped = "" if php else " (php — пропущено: нет php и контейнера)"
    print(f"блоков go: {counts['go']}, php: {counts['php']}{skipped}; находок: {len(problems)}")
    for x in problems:
        print(f"  {x}")
    if problems:
        return 1
    return 0 if php else 2  # php не проверен — не «зелёный»


if __name__ == "__main__":
    sys.exit(main())
