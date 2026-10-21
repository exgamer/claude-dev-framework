#!/usr/bin/env python3
"""Механические правила фреймворка на проекте — конфигом фреймворка, без файлов в проекте.

Конфиг анализатора собирается под проект (модуль из `go.mod`, домены из `internal/domains/*`) и пишется вне проекта —
в `~/.cache/dev-framework-lint/<проект>/`; свои конфиги проекта (`.golangci.yml`, `phpstan.neon`) не читаются и не меняются.
Правила — те же, что в эталоне: конфиг эталона собран этим же генератором (`--render` и сравнение — в
`tools/check/lint-rules.py`), поэтому доказательство на эталоне (`tools/check/lint-proof/go.py`) доказывает и шаблон.

  lint.py go <проект> [--base REV]   — прогон; --base — только новое относительно ревизии (дифф задачи) и ещё
                                       глушение проверок в добавленных строках диффа (tools/lint/diff_silence.py, оба стека)
  lint.py go <проект> --render       — напечатать собранный конфиг (для сверки и чтения)
  lint.py php <проект> --sub <подпроект> [--container NAME] [--base REV]  (контейнер не задан — найдёт по монтированию или спросит и запомнит)
                                     — PHP: phpstan фреймворка в PHP-контейнере проекта, только правила фреймворка

Go: `golangci-lint` v2 на машине (ставится один раз, отдельно от проекта). PHP: инструменты фреймворка (phpstan, phpat,
phpstan-disallowed-calls — без зависимостей от Laravel) ставятся в контейнер в `/tmp/df-lint`, не в проект; автозагрузка
кода — `vendor/autoload.php` проекта; проверки phpstan по уровням не включаются (`customRulesetUsed`) — только правила
фреймворка, ошибки проекта по его собственному phpstan не смешиваются.
"""
import argparse
import hashlib
import os
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import php_container  # noqa: E402

CACHE = Path.home() / ".cache" / "dev-framework-lint"

HEADER = """# Механические правила эталона: каждое правило ниже — отражение правила памятки,
# включено только после проверки, что ловит заведомое нарушение и не шумит на эталоне.
# Собран tools/lint/lint.py фреймворка (lint.py go <проект> --render); правка — в генераторе, не здесь.
version: "2"

run:
  tests: true
  relative-path-mode: gomod

linters:
  default: none
  enable:
    - depguard # слои и границы доменов
    - errcheck # ошибка не игнорируется, в т. ч. через _
    - forbidigo # запрещённые вызовы
    - nlreturn # пустая строка перед return/continue/break
    - revive # ctx первым, ID в именах
  settings:
    depguard:
      rules:
"""

FOOTER = """    errcheck:
      check-blank: true # `_ =` и `v, _ :=` для ошибки — тоже потеря (checklist.md п. 20)
      # CaptureSoft/CaptureError возвращают ту же, уже отправленную в трекер ошибку — вызов отдельной строкой (conventions.md п. 8, 19)
      exclude-functions:
        - git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter.CaptureSoft
        - git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter.CaptureError
        - github.com/exgamer/gosdk-core/pkg/errorreporter.CaptureSoft
        - github.com/exgamer/gosdk-core/pkg/errorreporter.CaptureError
    forbidigo:
      forbid:
        - pattern: '^context\\.(Background|TODO)$'
          msg: "в бизнес-коде ctx приходит параметром; Background — только в main/console/тестах"
      analyze-types: false
    nlreturn:
      block-size: 1
    revive:
      rules:
        - name: context-as-argument
        - name: var-naming
  exclusions:
    # std-error-handling: ошибка Close/Flush/os.Remove/печати не проверяется errcheck — отложенное закрытие после чтения
    # (conventions.md п. 8); закрытие после записи (файл, буферизованный writer) проверяет ревьюер
    presets: [std-error-handling]
    rules:
      # main, cmd/, console, app — точки запуска: корневой ctx создаётся здесь (conventions.md п. 1)
      - path: '(^|/)(main\\.go|cmd/|internal/console/|internal/app/)'
        linters: [forbidigo]
      # тесты создают свой ctx — правило о бизнес-коде (conventions.md п. 1)
      - path: '_test\\.go$'
        linters: [forbidigo]
      # ctx — правило бизнес-кода (conventions.md п. 1): pkg/ и миграции — не бизнес-код
      - path: '(^|/)(pkg/|internal/migrations/)'
        linters: [forbidigo]
      # код тестов вне _test.go (structure.md): e2e-обвязка tests/ и общие фейки internal/testsupport/
      - path: '(^|/)(tests/|internal/testsupport/)'
        linters: [forbidigo]
      # var-naming — только аббревиатуры (checklist.md п. 34); ALL_CAPS и подчёркивания правило фреймворка не запрещает
      - linters: [revive]
        text: "(don't use ALL_CAPS|don't use an underscore|don't use underscores|should not use underscores)"

formatters:
  enable:
    - gci # импорты тремя группами: std / внешние / свои (checklist.md п. 33)
  settings:
    gci:
      sections: [standard, default, localmodule]
"""

COMMON_DESC = "internal/common — общий словарь без зависимостей от слоёв"


def deny(pkg, desc):
    return f'            - pkg: "{pkg}"\n              desc: "{desc}"\n'


def module_of(project: Path) -> str:
    m = re.search(r"^module\s+(\S+)", (project / "go.mod").read_text(encoding="utf-8"), re.M)
    if not m:
        sys.exit(f"нет module в {project / 'go.mod'}")
    return m.group(1)


def domains_of(project: Path):
    root = project / "internal" / "domains"
    return sorted(p.name for p in root.iterdir() if p.is_dir()) if root.is_dir() else []


def render_go(project: Path) -> str:
    mod, domains = module_of(project), domains_of(project)
    # internal/domains/common — общий модуль проекта внутри domains (не домен): его знает любой домен, он — ни одного
    shared = "common" in domains
    domains = [d for d in domains if d != "common"]
    out = [HEADER]
    out.append('        domain-no-infra:\n          files:\n            - "**/internal/domains/**"\n'
               '            - "!$test" # правило — о коде пакета, тесты памятка не ограничивает\n          deny:\n')
    for sub, desc in [("infrastructure", "домен не знает инфраструктуру — порт в домене, адаптер в infrastructure"),
                      ("entrypoints", "домен не знает точки входа"), ("workflows", "домен не вызывает workflow"),
                      ("app", "домен не знает сборку")]:
        out.append(deny(f"{mod}/internal/{sub}", desc))
    out.append(deny("gorm.io", "домен без ORM — модель и запросы в infrastructure"))
    out.append(deny("github.com/gin-gonic", "домен без HTTP"))
    out.append(deny("net/http", "домен без HTTP"))
    if len(domains) > 1:
        out.append("        # Домены проекта не импортируют друг друга — по правилу на каждый домен (список доменов — internal/domains/*)\n")
        for d in domains:
            out.append(f'        {d}-no-other-domains:\n          files:\n            - "**/internal/domains/{d}/**"\n'
                       '            - "!$test"\n          deny:\n')
            for other in domains:
                if other != d:
                    out.append(deny(f"{mod}/internal/domains/{other}", "чужой домен — через workflow или internal/common"))
    # Модуль домена не импортирует соседний модуль того же домена; общее модулей — в internal/domains/<домен>/common
    # (он модулей не знает). Точное совпадение пути ($) и подпакеты (/) — чтобы tariff не задевал tariff_rule;
    # в имени правила «_» модуля — «-» (имена правил — [a-z0-9-])
    for d in domains:
        modules = sorted(p.name for p in (project / "internal" / "domains" / d).iterdir() if p.is_dir())
        siblings = [m for m in modules if m != "common"]
        for m in modules:
            others = siblings if m == "common" else [o for o in siblings if o != m]
            if not others:
                continue
            desc = ("общее домена модулей не знает" if m == "common"
                    else f"соседний модуль домена {d} — связь через интерфейс модуля-потребителя (O-6, modules.go), общее — internal/domains/{d}/common")
            out.append(f'        {d}-{m.replace("_", "-")}-no-other-modules:\n          files:\n            - "**/internal/domains/{d}/{m}/**"\n'
                       '            - "!$test"\n          deny:\n')
            for o in others:
                out.append(deny(f"{mod}/internal/domains/{d}/{o}$", desc))
                out.append(deny(f"{mod}/internal/domains/{d}/{o}/", desc))
    if shared and domains:
        out.append('        domains-common-no-domains:\n          files:\n            - "**/internal/domains/common/**"\n'
                   '            - "!$test"\n          deny:\n')
        for other in domains:
            out.append(deny(f"{mod}/internal/domains/{other}", "общий модуль домены не знает — то же правило, что internal/common (common-with-logic)"))
    out.append('        workflow-no-workflow:\n          files:\n            - "**/internal/workflows/**"\n'
               '            - "!$test" # внешний тест workflow импортирует сам workflow\n          deny:\n')
    out.append(deny(f"{mod}/internal/workflows", "workflow не вызывает другой workflow"))
    out.append(deny(f"{mod}/internal/infrastructure", "workflow берёт интерфейсы доменов, не инфраструктуру"))
    out.append('        entrypoint-no-infra:\n          files:\n            - "**/internal/entrypoints/**"\n'
               '            - "!$test"\n          deny:\n')
    out.append(deny(f"{mod}/internal/infrastructure", "точка входа — через сервис/workflow, инфраструктура собирается в bootstrap"))
    out.append(deny(f"{mod}/internal/app", "точка входа не знает сборку"))
    out.append('        common-no-internal:\n          files:\n            - "**/internal/common/**"\n'
               '            - "!$test" # внешний тестовый пакет импортирует сам common\n          deny:\n')
    for sub in ("domains", "infrastructure", "entrypoints", "workflows", "app"):
        out.append(deny(f"{mod}/internal/{sub}", COMMON_DESC))
    # pkg/ — код без домена: может зависеть от транспорта (gin) и SDK ответа, не от internal/** (structure.md, «pkg/»)
    out.append('        pkg-no-internal:\n          files:\n            - "**/pkg/**"\n          deny:\n')
    out.append(deny(f"{mod}/internal", "pkg/ — код без домена: internal/** (в т. ч. internal/common) не импортирует; нужен доменный тип — код в слое домена"))
    out.append(FOOTER)
    return "".join(out)


GOCHECK_SRC = Path(__file__).resolve().parent / "gocheck"


def gocheck_binary() -> Path:
    """Анализатор фреймворка (tools/lint/gocheck, только stdlib) — собирается в кеш один раз на версию исходника."""
    tag = hashlib.sha1((GOCHECK_SRC / "main.go").read_bytes()).hexdigest()[:10]
    binary = CACHE / f"gocheck-{tag}"
    if not binary.exists():
        CACHE.mkdir(parents=True, exist_ok=True)
        env = dict(os.environ, GOFLAGS="-mod=mod", GOPROXY="off", GOWORK="off")
        r = subprocess.run(["go", "build", "-o", str(binary), "."], cwd=GOCHECK_SRC, env=env, capture_output=True, text=True)
        if r.returncode:
            sys.exit("не собрался tools/lint/gocheck:\n" + r.stderr)
    return binary


# Агенты делали `git add -N`, чтобы новые файлы попали в проверку: они и так учтены (git ls-files --others)
NEW_FILES_NOTE = "новые файлы задачи (не в git): {} — учтены, `git add` не нужен"


def changed_go_files(project: Path, base) -> list[str]:
    """Изменённые и новые .go; неверная ревизия или не git — ошибка (код 2), а не «изменённых нет»."""
    d = subprocess.run(["git", "diff", "--name-only", "--diff-filter=ACMR", base, "--", "*.go"], cwd=project,
                       capture_output=True, text=True)
    if d.returncode:
        print(f"gocheck: git diff {base} не выполнился — {d.stderr.strip()}", flush=True)
        sys.exit(2)
    diff = d.stdout.split()
    new = subprocess.run(["git", "ls-files", "--others", "--exclude-standard", "--", "*.go"], cwd=project,
                         capture_output=True, text=True).stdout.split()
    prefix = subprocess.run(["git", "rev-parse", "--show-prefix"], cwd=project, capture_output=True, text=True).stdout.strip()
    print(NEW_FILES_NOTE.format(len(new)), flush=True)
    return sorted({f.removeprefix(prefix) for f in diff} | set(new))


def run_gocheck(project: Path, base) -> int:
    cmd, stdin = [str(gocheck_binary()), str(project)], None
    if base:
        files = changed_go_files(project, base)
        if not files:
            print("gocheck: изменённых .go нет", flush=True)
            return 0
        cmd.append("-")  # список — через stdin: большой дифф не упирается в длину командной строки
        stdin = "\n".join(files) + "\n"
    print(f"$ gocheck {project}{' (' + str(len(files)) + ' изменённых файлов)' if base else ''}", flush=True)
    r = subprocess.run(cmd, input=stdin, capture_output=True, text=True)
    sys.stdout.write(r.stdout + r.stderr)
    return r.returncode


def run_go(project: Path, base):
    cfg_dir = CACHE / (project.name + "-" + hashlib.sha1(str(project).encode()).hexdigest()[:8])
    cfg_dir.mkdir(parents=True, exist_ok=True)
    cfg = cfg_dir / "golangci.yml"
    cfg.write_text(render_go(project), encoding="utf-8")
    cmd = ["golangci-lint", "run", "--config", str(cfg)]
    if base:
        cmd += ["--new-from-rev", base]
    found = domains_of(project)
    shared = " (internal/domains/common — общий модуль, не домен)" if "common" in found else ""
    print(f"домены (правила «домен не импортирует другой»): {', '.join(d for d in found if d != 'common') or 'нет internal/domains'}{shared}", flush=True)
    print(f"конфиг: {cfg}\n$ {' '.join(cmd + ['./...'])}", flush=True)
    env = dict(os.environ, PWD=str(project))  # $PWD — алиас-симлинк проекта даёт ложный «0 issues»
    res = subprocess.run(cmd + ["./..."], cwd=project, env=env, capture_output=True, text=True)
    out = res.stdout + res.stderr
    # Тесты проекта не компилируются (ошибка типизации в *_test.go) — она прячет анализ пакета: повтор без тестов
    if res.returncode and re.search(r"_test\.go:\d+:\d+: .*\(typecheck\)", out):
        print("тесты проекта не компилируются — анализ без тестов (--tests=false); ошибка тестов — проблема проекта:", flush=True)
        print("\n".join(l for l in out.splitlines() if "_test.go" in l and "(typecheck)" in l), flush=True)
        res = subprocess.run(cmd + ["--tests=false", "./..."], cwd=project, env=env, capture_output=True, text=True)
        out = res.stdout + res.stderr
    sys.stdout.write(out)
    # правила без линтера в golangci (имена файлов, теги validate, SetTimeout пагинатора) — свой анализатор по AST
    own = run_gocheck(project, base)  # и при находках golangci — иначе находки gocheck прятались бы за ними
    return res.returncode or own


PHP_TOOLS = "/tmp/df-lint"
PHP_TOOLS_COMPOSER = """{
    "require": {
        "phpstan/phpstan": "^2.1",
        "phpat/phpat": "^0.11",
        "spaze/phpstan-disallowed-calls": "^4.0"
    },
    "autoload": {
        "psr-4": {
            "DevFrameworkLint\\\\": "rules/"
        }
    }
}
"""


def docker(container, cmd, stdin=None):
    return subprocess.run(["docker", "exec", "-i", container, "sh", "-c", cmd], input=stdin, capture_output=True, text=True)


def container_path(container: str, host: Path) -> str:
    """Путь проекта внутри контейнера — по монтированиям контейнера."""
    import json
    mounts = json.loads(subprocess.check_output(["docker", "inspect", container, "--format", "{{json .Mounts}}"]))
    for m in sorted(mounts, key=lambda m: -len(m["Source"])):
        src = Path(m["Source"]).resolve()
        if host == src or src in host.parents:
            return m["Destination"].rstrip("/") + "/" + str(host.relative_to(src))
    sys.exit(f"проект {host} не смонтирован в контейнер {container}")


def php_namespace(project: Path, sub: str) -> str:
    import json
    psr4 = json.loads((project / "composer.json").read_text(encoding="utf-8")).get("autoload", {}).get("psr-4", {})
    for ns, path in psr4.items():
        for p in (path if isinstance(path, list) else [path]):
            if p.strip("/") == sub.strip("/"):
                return ns.rstrip("\\")
    sys.exit(f"нет psr-4 для {sub} в composer.json проекта")


def phpstan_run(cpath: str, sub: str, work: str, files: list[str]) -> tuple[str, str]:
    """Конфиг phpstan и команда: изменённые файлы (--base) — в parameters.paths конфига, не аргументами командной строки
    (тысячи путей через `docker exec` — «argument list too long»); без файлов — весь подпроект."""
    paths = "".join("        - '" + f.replace("'", "''") + "'\n" for f in files) if files else f"        - {cpath}/{sub}\n"
    neon = f"""includes:
    - {PHP_TOOLS}/vendor/phpat/phpat/extension.neon
    - {PHP_TOOLS}/vendor/spaze/phpstan-disallowed-calls/extension.neon
    - rules.neon

parameters:
    customRulesetUsed: true
    paths:
{paths}    bootstrapFiles:
        - {cpath}/vendor/autoload.php
    tmpDir: {work}/cache
"""
    cmd = f"cd {cpath} && php -d memory_limit=2G {PHP_TOOLS}/vendor/bin/phpstan analyse -c {work}/phpstan.neon --no-progress"
    return neon, cmd


def run_php(project: Path, sub: str, container: str, base, no_install=False):
    sys.path.insert(0, str(Path(__file__).parent))
    import php_rules
    root = php_namespace(project, sub)
    dom_dir = project / sub / "Domains"
    domains = sorted(p.name for p in dom_dir.iterdir() if p.is_dir()) if dom_dir.is_dir() else []
    cpath = container_path(container, project)
    tag = hashlib.sha1(str(project).encode()).hexdigest()[:8]
    work = f"{PHP_TOOLS}/{project.name}-{tag}"
    # Класс правил phpat нужен уже при сборке DI-контейнера phpstan — он в автозагрузке инструментов, не проекта
    ns = f"DevFrameworkLint\\P{tag}"
    have = docker(container, f"cat {PHP_TOOLS}/composer.json 2>/dev/null").stdout
    if docker(container, f"test -x {PHP_TOOLS}/vendor/bin/phpstan").returncode or "DevFrameworkLint" not in have:
        if no_install:
            print(f"инструменты фреймворка в {container}:{PHP_TOOLS} не установлены, а --no-install — не запускался", flush=True)
            return 2
        print(f"ставлю инструменты фреймворка в {container}:{PHP_TOOLS} (не в проект)", flush=True)
        docker(container, f"mkdir -p {PHP_TOOLS} && cat > {PHP_TOOLS}/composer.json", PHP_TOOLS_COMPOSER)
        r = docker(container, f"cd {PHP_TOOLS} && rm -f composer.lock && COMPOSER_NO_INTERACTION=1 composer update --no-progress -q")
        if r.returncode:
            sys.stderr.write(r.stdout + r.stderr)
            return 2
    docker(container, f"mkdir -p {work}/cache")
    docker(container, f"mkdir -p {PHP_TOOLS}/rules/P{tag}")
    docker(container, f"cat > {PHP_TOOLS}/rules/P{tag}/LayersArchitecture.php", php_rules.render_arch(ns, root, domains, php_rules.domain_modules(dom_dir)))
    for name, code in php_rules.render_own_rules(ns).items():
        docker(container, f"cat > {PHP_TOOLS}/rules/P{tag}/{name}.php", code)
    docker(container, f"cat > {work}/rules.neon", php_rules.render_neon(ns + "\\LayersArchitecture"))
    files = []
    if base:
        diff = subprocess.check_output(["git", "diff", "--name-only", "--diff-filter=ACMR", base, "--", sub], cwd=project).decode().split()
        # новые файлы задачи ещё не в git — git diff их не показывает, а нарушение в них — тоже нарушение
        new = subprocess.check_output(["git", "ls-files", "--others", "--exclude-standard", "--", sub], cwd=project).decode().split()
        print(NEW_FILES_NOTE.format(len([f for f in new if f.endswith(".php")])), flush=True)
        diff += new
        files = [f"{cpath}/{f}" for f in sorted(set(diff)) if f.endswith(".php")]
        if not files:
            print(f"нет изменённых PHP-файлов в {sub} относительно {base}")
            return 0
    neon, cmd = phpstan_run(cpath, sub, work, files)
    docker(container, f"cat > {work}/phpstan.neon", neon)  # через stdin — длина списка файлов не ограничена
    shared = f" ({sub}/Domains/Common — общий модуль, не домен)" if "Common" in domains else ""
    print(f"домены (правила «домен не знает другие»): {', '.join(d for d in domains if d != 'Common') or 'нет ' + sub + '/Domains'}{shared}", flush=True)
    print(f"правила: {container}:{work}\n$ {cmd}", flush=True)
    r = docker(container, cmd + " 2>&1")
    sys.stdout.write("\n".join(l for l in r.stdout.splitlines() if "Deprecated: PHP Startup" not in l) + "\n")
    # маршруты (api.response первым, id в пути без ограничения, 403 у permission:) — разбором routes.php на хосте, без PHP
    import php_routes
    sys.stdout.flush()
    routes = php_routes.main([str(project / sub), root] + (["--base", base] if base else []))  # с --base — добавленные строки
    return r.returncode or routes


RULES_TABLE = Path(__file__).resolve().parent.parent / "check" / "lint-proof" / "RULES.md"


def remainder(stack: str) -> str:
    """Остаток по правилам стека из RULES.md — что прогон не видит: ревьюеру не нужно открывать таблицу целиком."""
    section, lines = None, []
    for line in RULES_TABLE.read_text(encoding="utf-8").splitlines():
        if line.startswith("## "):
            section = line[3:].split(" ")[0]
            continue
        if section != stack or not line.startswith("| ") or line.startswith("| Правило") or line.startswith("|---"):
            continue
        cells = [c.strip() for c in line.strip().strip("|").split(" | ")]
        if len(cells) == 7:
            lines.append(f"- {cells[0]} — {cells[1]}, правило `{cells[2]}`: {cells[4]}")  # источник — какой пункт памятки
    return ("Остаток — проверить глазами (что прогон не видит; находка прогона — тег из строки её правила, уровень — по каталогу; "
            "пункты памятки не из этого списка — целиком):\n" + "\n".join(lines) + "\n")


def finished(stack: str, output: str) -> bool:
    """Прогон дошёл до конца (с находками или без), а не упал."""
    if stack == "Go":
        # typecheck вне тестов — код не разобран, остальные линтеры пакет пропустили; тесты — повтор с --tests=false
        broken = [l for l in output.splitlines() if "(typecheck)" in l and "_test.go:" not in l]
        diff_failed = "Can't process results by diff processor" in output  # --new-from-rev не применился
        # gocheck — итоговая строка обязательна: упал (нет прав, не собрался) — анализ не полный
        own = re.search(r"^gocheck: (файлов: \d+; находок: \d+|изменённых \.go нет)$", output, re.M)
        return bool(re.search(r"^\d+ issues?[.:]", output, re.M)) and "level=error" not in output and not broken \
            and not diff_failed and bool(own)
    if "Result is incomplete" in output:  # phpstan: синтаксическая ошибка в файле — правила по нему не отработали
        return False
    return bool(re.search(r"\[OK\] No errors|Found [\d+]+ errors?|нет изменённых PHP-файлов", output))


def main():
    p = argparse.ArgumentParser(description="Механические правила фреймворка на проекте, без файлов в проекте")
    p.add_argument("stack", choices=["go", "php"])
    p.add_argument("project")
    p.add_argument("--base", help="ревизия, от которой смотреть новое (дифф задачи)")
    p.add_argument("--render", action="store_true", help="только напечатать собранный конфиг (go)")
    p.add_argument("--sub", help="php: каталог подпроекта (psr-4 из composer.json проекта), например parking_app")
    p.add_argument("--no-install", action="store_true", help="php: не ставить инструменты (агент ревью — без сети): нет — «не запускался», код 2")
    p.add_argument("--container", help="php: контейнер с PHP проекта (по умолчанию — найдёт или спросит, tools/lint/php_container.py)")
    a = p.parse_args()
    if a.render:
        sys.stdout.write(render_go(Path(a.project).expanduser().resolve()))
        return 0
    tee = Tee(sys.stdout)
    sys.stdout = tee
    try:
        code = run(a)
    finally:
        sys.stdout = tee.out
    stack = "Go" if a.stack == "go" else "PHP"
    if finished(stack, tee.text()):
        sys.stdout.write("\n" + remainder(stack))
        if "--tests=false" in tee.text():
            sys.stdout.write("тесты проекта не компилируются — прогон шёл без тестов: тесты задачи по правилам списка — глазами целиком\n")
    else:
        sys.stdout.write("\nпрогон не дошёл до конца — остатка нет: пункты памятки проверяются целиком\n")
    return code


class Tee:
    """Копия вывода прогона — чтобы понять, дошёл ли он до конца."""

    def __init__(self, out):
        self.out, self.parts = out, []

    def write(self, s):
        self.parts.append(s)
        return self.out.write(s)

    def flush(self):
        self.out.flush()

    def text(self):
        return "".join(self.parts)


def check_base(project: Path, base):
    """--base — существующая ревизия git проекта; иначе дифф не применяется и инструмент молча проверяет не то."""
    if base and subprocess.run(["git", "rev-parse", "--verify", "--quiet", f"{base}^{{commit}}"], cwd=project,
                               capture_output=True).returncode:
        print(f"--base {base}: нет такой ревизии в git проекта {project} — прогон не выполнен", flush=True)
        sys.exit(2)


def run(a):
    project = Path(a.project).expanduser().resolve()
    check_base(project, a.base)
    if a.stack == "php":
        if not a.sub:
            sys.exit("php: нужен --sub <каталог подпроекта>")
        container = php_container.resolve(project, a.container)
        if not container:
            sys.exit(php_container.hint(project))
        code = run_php(project, a.sub, container, a.base, a.no_install)
    else:
        code = run_go(project, a.base)
    if a.base:
        # глушение проверок (`//nolint`, `@phpstan-ignore`, пропуск теста, ослабление конфига) — только в добавленных строках диффа
        import diff_silence
        sys.stdout.flush()
        code = diff_silence.check(project, a.base, a.sub if a.stack == "php" else None) or code
    return code


if __name__ == "__main__":
    sys.exit(main())
