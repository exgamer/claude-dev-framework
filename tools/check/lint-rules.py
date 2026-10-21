#!/usr/bin/env python3
"""Правила, отданные анализаторам, не потерялись и вызываются там, где надо (`tools/check/lint-proof/RULES.md`).

Запуск из корня репозитория: python3 tools/check/lint-rules.py
Код возврата: 0 — чисто, 1 — есть находки.

По каждой строке таблицы: файл-источник правила существует (и пункт памятки, если указан); тег есть в каталогах
(`tools/check/tags.py`); правило есть в конфиге стека; каждый случай есть в `CASES` скрипта доказательства; каждый
файл «Где запускается» вызывает прогон стека. Обратно: каждое правило конфига и каждый случай доказательства
упомянуты в таблице — правило в конфиге без строки таблицы или без доказательства — находка.
"""
import os
import re
import subprocess
import sys

sys.path.insert(0, os.path.dirname(__file__))
import tags as tagcheck  # noqa: E402

TABLE = "tools/check/lint-proof/RULES.md"
STACKS = {
    "Go": {
        "configs": ["stacks/go/examples/tariff/.golangci.yml", "tools/lint/gocheck/main.go"],
        "proof": "tools/check/lint-proof/go.py",
        "runs": {"skills/dev-rule/SKILL.md": "tools/lint/lint.py go", "approaches/process/self-check.md": "tools/lint/lint.py",
                 "approaches/process/review.md": "tools/lint/lint.py"},
    },
    "PHP": {
        "configs": ["stacks/php/examples/phpstan-rules.neon", "stacks/php/examples/tests/Architecture/LayersArchitecture.php",
                    "stacks/php/examples/tests/Architecture/GenericExceptionRule.php"],
        "proof": "tools/check/lint-proof/php.py",
        "runs": {"skills/dev-rule/SKILL.md": "php-check/run.sh", "tools/php-check/run.sh": "phpstan analyse",
                 "approaches/process/self-check.md": "tools/lint/lint.py", "approaches/process/review.md": "tools/lint/lint.py"},
    },
}


def read(path):
    return open(path, encoding="utf-8").read()


def table_rows():
    rows, stack = [], None
    for line in read(TABLE).split("\n"):
        if line.startswith("## Go"):
            stack = "Go"
        elif line.startswith("## PHP"):
            stack = "PHP"
        if stack and line.startswith("| ") and not line.startswith("| Правило") and not line.startswith("|---"):
            cells = [c.strip() for c in line.strip().strip("|").split(" | ")]
            if len(cells) == 7:
                rows.append((stack, cells))
    return rows


def config_rules(stack):
    """Правила, которые реально объявлены в конфигах стека."""
    if stack == "Go":
        text = read(STACKS["Go"]["configs"][0])
        rules = set(re.findall(r"^        ([a-z0-9-]+):\n          files:", text, re.M))
        enabled = set(re.findall(r"^    - ([a-z0-9]+)", text, re.M))
        rules |= {e for e in enabled if e in ("forbidigo", "nlreturn")}
        rules |= {f"revive:{r}" for r in re.findall(r"- name: ([a-z0-9-]+)", text)}
        rules |= {f"gocheck:{t}" for t in re.findall(r"\(([a-z0-9-]+)\)\"", read(STACKS["Go"]["configs"][1]))}
        return rules
    neon = read(STACKS["PHP"]["configs"][0])
    arch = read(STACKS["PHP"]["configs"][1])
    rules = set(re.findall(r"public function (test_[a-z0-9_]+)\(", arch))
    rules |= {f"disallowed:{c}" for c in re.findall(r"class: '([^']+)'", neon) if "\\" in c and "Tests\\" not in c}
    rules |= {f"disallowed:{f}" for f in re.findall(r"- '([a-z_]+\(\))'", neon)}
    rules |= {f"rule:{r}" for r in re.findall(r"^    - [\w\\]+\\(\w+Rule)$", neon, re.M)}
    return rules


def proof_cases(stack):
    text = read(STACKS[stack]["proof"])
    return set(re.findall(r'^\s*\("([^"]+)",', text, re.M))


def main():
    catalog = tagcheck.catalog()
    problems = []
    seen_rules = {s: set() for s in STACKS}
    seen_cases = {s: set() for s in STACKS}
    rows = table_rows()
    for stack, (source, tag, rule, exact, rest, cases, runs) in rows:
        where = f"{TABLE}: {stack}: {rule}"
        if not exact.strip() or not rest.strip():
            problems.append(f"{where}: пустая колонка «Ловит точно» или «Остаток — агенту»")
        if not [c for c in cases.split(";") if c.strip() and not c.strip().startswith("разрешено")]:
            problems.append(f"{where}: нет ни одного случая-нарушения — «Ловит точно» не доказано")
        for path, item in re.findall(r"`([^`]+\.md)`(?:\s*п\.\s*([0-9]+[a-z]?))?", source):
            if not os.path.isfile(path):
                problems.append(f"{where}: нет файла-источника {path}")
            elif item and not re.search(rf"^{re.escape(item)}\.\s", read(path), re.M):
                problems.append(f"{where}: в {path} нет п. {item}")
        t = tag.strip("`")
        if t not in catalog:
            problems.append(f"{where}: тег `{t}` не найден в каталогах")
        declared = config_rules(stack)
        for r in [x.strip() for x in rule.split(";")]:
            seen_rules[stack].add(r)
            if r not in declared:
                problems.append(f"{where}: правила «{r}» нет в конфиге")
        known = proof_cases(stack)
        for c in [x.strip() for x in cases.split(";")]:
            seen_cases[stack].add(c)
            if c not in known:
                problems.append(f"{where}: случая «{c}» нет в {STACKS[stack]['proof']}")
        for path in re.findall(r"`([^`]+)`", runs):
            pattern = STACKS[stack]["runs"].get(path)
            if not os.path.isfile(path):
                problems.append(f"{where}: нет файла запуска {path}")
            elif pattern is None or pattern not in read(path):
                problems.append(f"{where}: {path} не вызывает прогон {stack} ({pattern or 'не известен шаблон вызова'})")
    # Конфиг эталона Go — вывод генератора tools/lint/lint.py: шаблон и эталон не расходятся
    rendered = subprocess.run([sys.executable, "tools/lint/lint.py", "go", "stacks/go/examples/tariff", "--render"],
                              capture_output=True, text=True).stdout
    if rendered != read(STACKS["Go"]["configs"][0]):
        problems.append("Go: stacks/go/examples/tariff/.golangci.yml не совпадает с выводом tools/lint/lint.py go … --render")
    # Правила PHP-эталона — вывод tools/lint/php_rules.py: шаблон и эталон не расходятся
    sys.path.insert(0, "tools/lint")
    import php_rules  # noqa: E402
    if php_rules.render_arch("Tests\\Architecture", "App\\ParkingApp", ["Billing", "Catalog"]) != read(STACKS["PHP"]["configs"][1]):
        problems.append("PHP: tests/Architecture/LayersArchitecture.php эталона не совпадает с выводом tools/lint/php_rules.py")
    if php_rules.render_neon("Tests\\Architecture\\LayersArchitecture") != read(STACKS["PHP"]["configs"][0]):
        problems.append("PHP: phpstan-rules.neon эталона не совпадает с выводом tools/lint/php_rules.py")
    if php_rules.render_exception_rule("Tests\\Architecture") != read(STACKS["PHP"]["configs"][2]):
        problems.append("PHP: tests/Architecture/GenericExceptionRule.php эталона не совпадает с выводом tools/lint/php_rules.py")
    # Ревьюер берёт остаток из вывода lint.py, не из таблицы: каждая строка таблицы — в его списке
    import lint  # noqa: E402
    # «Дошёл до конца» — по итогу инструмента; форматы итога, встреченные на проектах (иначе остаток молча не печатается
    # или печатается после падения)
    for stack, text, want in [
        ("Go", "0 issues.\ngocheck: файлов: 3; находок: 0", True), ("Go", "12 issues:\n* depguard: 5\ngocheck: файлов: 3; находок: 1", True),
        ("Go", "1 issue.\ngocheck: изменённых .go нет", True),
        ("Go", "0 issues.\nopen _x: permission denied", False),
        ("Go", "x.go:1:2: expected ';' (typecheck)\n1 issues:\ngocheck: файлов: 3; находок: 0", False),
        ("Go", "a_test.go:3:4: undefined: x (typecheck)\n0 issues.\ngocheck: файлов: 3; находок: 0", True),
        ("Go", "level=error msg=\"Running error\"\ngocheck: файлов: 3; находок: 0", False),
        ("Go", "level=warning msg=\"[runner] Can't process results by diff processor: x\"\n0 issues.\ngocheck: файлов: 3; находок: 0", False),
        ("Go", "gocheck: файлов: 3; находок: 0", False),
        ("PHP", " [OK] No errors", True), ("PHP", " [ERROR] Found 2 errors", True), ("PHP", " [ERROR] Found 1000+ errors", True),
        ("PHP", "Result is incomplete because of severe errors.\n [ERROR] Found 1 error", False),
        ("PHP", "PHP Fatal error: Allowed memory size", False),
    ]:
        if lint.finished(stack, text) != want:
            problems.append(f"lint.py finished({stack}, {text[:40]!r}) — не {want}")
    for stack in STACKS:
        n_rows = sum(1 for st, _ in rows if st == stack)
        n_out = lint.remainder(stack).count("\n- ")
        if n_out != n_rows:
            problems.append(f"{stack}: lint.py печатает остаток по {n_out} правилам, в таблице {n_rows}")
    for stack in STACKS:
        for r in sorted(config_rules(stack) - seen_rules[stack]):
            problems.append(f"{stack}: правило конфига «{r}» без строки в {TABLE}")
        for c in sorted(proof_cases(stack) - seen_cases[stack]):
            problems.append(f"{stack}: случай доказательства «{c}» без строки в {TABLE}")
    print(f"правил в таблице: {len(rows)}; находок: {len(problems)}")
    for p in problems:
        print(f"  {p}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
