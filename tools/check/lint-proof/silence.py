#!/usr/bin/env python3
"""Доказательство для `tools/lint/diff_silence.py` (глушение проверок в диффе, тег `check-silenced`).

Запуск из корня репозитория: python3 tools/check/lint-proof/silence.py
Каждый случай — временный git-репозиторий: файл «было» в коммите, «стало» в рабочем дереве, прогон с `--base HEAD`;
нарушение в добавленных строках ловится, с причиной и вне диффа — нет; с `--sub` — файл вне подпроекта не смотрится. Чистые эталоны (`stacks/go/examples/tariff`,
`stacks/php/examples`) целиком как дифф от пустой ревизии — 0 находок. Репозиторий фреймворка не меняется.
"""
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
TOOL = os.path.join(ROOT, "tools/lint/diff_silence.py")
GO_T = "package a\n\nimport \"testing\"\n\nfunc TestA(t *testing.T) {\n%s\n}\n"
PHP_T = "<?php\n\nfinal class ATest extends TestCase\n{\n    public function test_a(): void\n    {\n%s\n    }\n}\n"
YML = "version: \"2\"\nlinters:\n  default: none\n  enable:\n    - errcheck\n"
NEON = "parameters:\n    level: 6\n    paths:\n        - app\n"
CASES = [  # (имя, стек, файл, было (None — файла нет в git), стало, ловится ли)
    ("nolint без линтера и причины", "go", "a.go", "package a\n", "package a\n\nvar _ = 1 //nolint\n", True),
    ("nolint с линтером без причины", "go", "a.go", "package a\n", "package a\n\nvar _ = 1 //nolint:errcheck\n", True),
    ("разрешено: nolint с линтером и причиной", "go", "a.go", "package a\n",
     "package a\n\nvar _ = 1 //nolint:errcheck // тело уже прочитано, ошибка закрытия не влияет на ответ\n", False),
    ("разрешено: nolint вне диффа", "go", "a.go", "package a\n\nvar _ = 1 //nolint\n",
     "package a\n\nvar _ = 1 //nolint\n\nvar _ = 2\n", False),
    ("nolint в новом файле не из git", "go", "b.go", None, "package a\n\nvar _ = 1 //nolint\n", True),
    ("t.Skip без причины", "go", "a_test.go", GO_T % "", GO_T % "\tt.Skip()", True),
    ("t.SkipNow без причины", "go", "a_test.go", GO_T % "", GO_T % "\tt.SkipNow()", True),
    ("t.Skip с пустым сообщением", "go", "a_test.go", GO_T % "", GO_T % '\tt.Skip("")', True),
    ("разрешено: t.Skip с причиной в сообщении", "go", "a_test.go", GO_T % "", GO_T % '\tt.Skip("нужен живой брокер")', False),
    ("разрешено: t.Skipf с причиной в сообщении", "go", "a_test.go", GO_T % "",
     GO_T % '\tt.Skipf("нет переменной %s — интеграционный прогон", "DB_DSN")', False),
    ("разрешено: t.Skip с причиной в fmt.Sprintf", "go", "a_test.go", GO_T % "",
     GO_T % '\tt.Skip(fmt.Sprintf("нет переменной %s", "DB_DSN"))', False),
    ("разрешено: t.Skip с причиной строкой выше", "go", "a_test.go", GO_T % "",
     GO_T % "\t// сценарий требует живой брокер, в unit-прогоне его нет\n\tt.Skip(\"нужен брокер\")", False),
    ("разрешено: x.Skip(n) вне теста — построитель запроса", "go", "a.go", "package a\n",
     "package a\n\ntype Q interface{ Skip(n int) Q }\n\nfunc f(q Q) Q { return q.Skip(10) }\n", False),
    ("golangci: exclusions без причины", "go", ".golangci.yml", YML,
     YML + "  exclusions:\n    rules:\n      - path: 'internal/'\n        linters: [errcheck]\n", True),
    ("golangci: disable без причины", "go", ".golangci.yml", YML, YML + "  disable:\n    - govet\n", True),
    ("разрешено: golangci exclusions с причиной", "go", ".golangci.yml", YML,
     YML + "  exclusions:\n    rules:\n      # сгенерированный клиент: правки затираются генератором\n"
           "      - path: 'internal/gen/'\n        linters: [errcheck]\n", False),
    ("phpstan-ignore без причины", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        // @phpstan-ignore-next-line\n        $a = foo();", True),
    ("phpstan-ignore с идентификатором без причины", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $a = foo(); // @phpstan-ignore argument.type", True),
    ("phpstan-ignore с идентификаторами строкой выше без причины", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        // @phpstan-ignore method.notFound, argument.type\n        $a = foo();", True),
    ("разрешено: phpstan-ignore-next-line с причиной комментарием", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        // @phpstan-ignore-next-line фасад тестового пакета без типов\n        $a = foo();", False),
    ("разрешено: phpstan-ignore с причиной в скобках", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $a = foo(); // @phpstan-ignore argument.type (тип проверен валидатором)", False),
    ("markTestSkipped без причины", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestSkipped();", True),
    ("markTestSkipped с пустым сообщением", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestSkipped('');", True),
    ("markTestIncomplete без причины", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestIncomplete();", True),
    ("разрешено: markTestSkipped с причиной в сообщении", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestSkipped('нет pcntl — параллельные обработчики не проверить');", False),
    ("разрешено: markTestSkipped с причиной в сообщении на следующей строке", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestSkipped(\n            'нет ext-intl в образе CI',\n        );", False),
    ("разрешено: markTestSkipped с причиной именованным аргументом", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        $this->markTestSkipped(message: 'нет pcntl');", False),
    ("разрешено: markTestSkipped с причиной комментарием", "php", "tests/ATest.php", PHP_T % "",
     PHP_T % "        // нужен ext-intl, в образе CI его нет\n        $this->markTestSkipped('нет ext-intl');", False),
    ("CodeCoverageIgnore без причины", "php", "app/A.php", "<?php\n\nfinal class A\n{\n}\n",
     "<?php\n\n#[\\PHPUnit\\Framework\\Attributes\\CodeCoverageIgnore]\nfinal class A\n{\n}\n", True),
    ("@codeCoverageIgnore без причины", "php", "app/A.php", "<?php\n\n/**\n * Класс.\n */\nfinal class A\n{\n}\n",
     "<?php\n\n/**\n * Класс.\n *\n * @codeCoverageIgnore\n */\nfinal class A\n{\n}\n", True),
    ("@codeCoverageIgnore после @author без причины", "php", "app/A.php", "<?php\n\n/**\n * Класс.\n */\nfinal class A\n{\n}\n",
     "<?php\n\n/**\n * Класс.\n *\n * @author Ivan Petrov\n * @codeCoverageIgnore\n */\nfinal class A\n{\n}\n", True),
    ("phpstan-ignore-next-line после @return без причины", "php", "app/A.php", "<?php\n\nfinal class A\n{\n}\n",
     "<?php\n\nfinal class A\n{\n    /**\n     * @return int\n     * @phpstan-ignore-next-line\n     */\n"
     "    public function a()\n    {\n        return foo();\n    }\n}\n", True),
    ("разрешено: @codeCoverageIgnore с причиной текстом в docblock", "php", "app/A.php",
     "<?php\n\n/**\n * Класс.\n */\nfinal class A\n{\n}\n",
     "<?php\n\n/**\n * Класс.\n *\n * @author Ivan Petrov\n * Генерируется сборкой, тестами не покрывается.\n"
     " * @codeCoverageIgnore\n */\nfinal class A\n{\n}\n", False),
    ("phpstan: level понижен", "php", "phpstan.neon", NEON, NEON.replace("level: 6", "level: 5"), True),
    ("разрешено: phpstan level повышен", "php", "phpstan.neon", NEON, NEON.replace("level: 6", "level: 7"), False),
    ("phpstan: ignoreErrors без причины", "php", "phpstan.neon", NEON,
     NEON + "    ignoreErrors:\n        -\n            message: '#^Call to an undefined method#'\n            path: app/A.php\n", True),
    ("phpstan: excludePaths без причины", "php", "phpstan.neon", NEON, NEON + "    excludePaths:\n        - app/Legacy\n", True),
    ("разрешено: phpstan ignoreErrors с причиной", "php", "phpstan.neon", NEON,
     NEON + "    ignoreErrors:\n        # шум типизации mps/core: fromArray возвращает static без дженерика\n"
            "        -\n            message: '#^Method .*fromArray\\(\\) should return#'\n", False),
]
SUB = "app_a"
SUB_CASES = [  # (имя, стек, файл, стало, ловится ли) — прогон с `--sub app_a`
    ("--sub: файл подпроекта смотрится", "php", "app_a/tests/ATest.php", PHP_T % "        $this->markTestSkipped();", True),
    ("разрешено: --sub: файл вне подпроекта не смотрится", "php", "other_app/tests/ATest.php",
     PHP_T % "        $this->markTestSkipped();", False),
    ("--sub: конфиг phpstan в корне проекта смотрится", "php", "phpstan.neon", NEON + "    excludePaths:\n        - app_a/Legacy\n", True),
]


def git(cwd, *args):
    subprocess.run(["git", "-c", "user.name=proof", "-c", "user.email=proof@example.com", *args], cwd=cwd,
                   check=True, capture_output=True)


def run(cwd, *extra):
    return subprocess.run([sys.executable, TOOL, cwd, "--base", "HEAD", *extra], capture_output=True, text=True)


def write(path, text):
    os.makedirs(os.path.dirname(path) or ".", exist_ok=True)
    open(path, "w", encoding="utf-8").write(text)


def main():
    fails = 0
    for name, _stack, path, before, after, caught in CASES:
        work = tempfile.mkdtemp(prefix="silenceproof-")
        git(work, "init", "-q")
        if before is not None:
            write(os.path.join(work, path), before)
            git(work, "add", "-A")
        git(work, "commit", "-q", "--allow-empty", "-m", "base")
        write(os.path.join(work, path), after)
        res = run(work)
        got = f"{path}:" in res.stdout and "[check-silenced]" in res.stdout
        ok = got == caught and res.returncode == (1 if caught else 0)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name + ("" if ok else "\n" + res.stdout + res.stderr))
        shutil.rmtree(work)
    # --sub (монорепозиторий): смотрятся файлы подпроекта и конфиги phpstan в корне проекта и подпроекта
    for name, _stack, path, after, caught in SUB_CASES:
        work = tempfile.mkdtemp(prefix="silenceproof-")
        git(work, "init", "-q")
        write(os.path.join(work, path), NEON if path.endswith(".neon") else "")
        git(work, "add", "-A")
        git(work, "commit", "-q", "-m", "base")
        write(os.path.join(work, path), after)
        res = run(work, "--sub", SUB)
        got = f"{path}:" in res.stdout and "[check-silenced]" in res.stdout
        ok = got == caught and res.returncode == (1 if caught else 0)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name + ("" if ok else "\n" + res.stdout + res.stderr))
        shutil.rmtree(work)
    # Эталоны целиком — дифф от пустой ревизии: каждая заглушка и запись конфига в них объяснена
    for src in ("stacks/go/examples/tariff", "stacks/php/examples"):
        work = tempfile.mkdtemp(prefix="silenceproof-")
        git(work, "init", "-q")
        git(work, "commit", "-q", "--allow-empty", "-m", "base")
        shutil.copytree(os.path.join(ROOT, src), os.path.join(work, "src"))
        res = run(work)
        last = (res.stdout.strip().splitlines() or [""])[-1]
        ok = res.returncode == 0 and last.endswith("находок: 0")
        fails += not ok
        print(f"чистый эталон {src}: {last}" + ("" if ok else "\n" + res.stdout + res.stderr))
        shutil.rmtree(work)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
