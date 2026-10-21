#!/usr/bin/env python3
"""Доказательство для `tools/check/codeblocks.py`: сломанный блок go/php ловится, допустимые фрагменты — нет.

Запуск из корня репозитория: python3 tools/check/lint-proof/codeblocks.py [--php-container <имя>]
"""
import argparse
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
import codeblocks  # noqa: E402

CASES = [  # (имя, язык, блок, должен разобраться)
    ("go: незакрытая скобка", "go", "func F() {\n\treturn 1\n", False),
    ("go: опечатка в операторе", "go", "x := := 1\n", False),
    ("go: строка без кавычки", "go", 'fmt.Println("x)\n', False),
    ("php: незакрытая скобка", "php", "public function f(): void\n{\n    $x = 1;\n", False),
    ("php: два выражения без ;", "php", "$a = 1\n$b = 2;\n", False),
    ("php: метод интерфейса без ;", "php", "public function f(array $ids): array\n", False),
    ("разрешено: php — методы интерфейса", "php", "/** @return int */\npublic function f(array $ids): array;\n", True),
    ("разрешено: go — тело функции", "go", "res, err := f(ctx)\nif err != nil {\n\treturn err\n}\n", True),
    ("разрешено: go — заглушка ...", "go", "conn, err := dial()\n...\n\nfunc G() {}\n", True),
    ("разрешено: go — список методов builder", "go", ".SetA(1) // a\n.SetB(2)\n", True),
    ("разрешено: php — метод с parent", "php", "protected static function boot(): void\n{\n    parent::boot();\n}\n", True),
    ("разрешено: php — атрибут", "php", "#[OA\\Get(path: '/x')]\n", True),
    ("разрешено: php — заглушки", "php", "$s->create(...);\nclass A { ... }\n$c->get($k, fn () => ..., 1);\n", True),
]


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--php-container")
    a = p.parse_args()
    php = codeblocks.php_cmd(a.php_container)
    checker = codeblocks.Checker(php)
    fails = 0
    for name, lang, src, good in CASES:
        if lang == "php" and not php:
            print("SKIP  " + name + " (нет php)")
            continue
        ok = checker.ok(lang, src) == good
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
    # блок с отступом в пункте списка — находится и проверяется (раньше пропускался молча)
    import textwrap
    doc = "- пункт:\n\n    ```go\n    func F( {\n    ```\n"
    blocks = list(codeblocks.BLOCK_RE.finditer(doc))
    ok = len(blocks) == 1 and not checker.ok("go", textwrap.dedent(blocks[0].group(3)))
    fails += not ok
    print(("OK    " if ok else "MISS  ") + "блок с отступом найден и сломан")
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
