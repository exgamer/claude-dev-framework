#!/usr/bin/env python3
"""Механические правила фреймворка на проекте — конфигом фреймворка, без файлов в проекте.

Конфиг анализатора собирается под проект (модуль из `go.mod`, домены из `internal/domains/*`) и пишется вне проекта —
в `~/.cache/dev-framework-lint/<проект>/`; свои конфиги проекта (`.golangci.yml`, `phpstan.neon`) не читаются и не меняются.
Правила — те же, что в эталоне: конфиг эталона собран этим же генератором (`--render` и сравнение — в
`tools/check/lint-rules.py`), поэтому доказательство на эталоне (`tools/check/lint-proof/go.py`) доказывает и шаблон.

  lint.py go <проект> [--base REV]   — прогон; --base — только новое относительно ревизии (дифф задачи)
  lint.py go <проект> --render       — напечатать собранный конфиг (для сверки и чтения)
  lint.py php <проект> --sub <подпроект> [--container NAME] [--base REV]
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
    - forbidigo # запрещённые вызовы
    - nlreturn # пустая строка перед return/continue/break
    - revive # ctx первым, ID в именах
  settings:
    depguard:
      rules:
"""

FOOTER = """    forbidigo:
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
    rules:
      - path: '(^|/)(main\\.go|cmd/|internal/console/|internal/app/)'
        linters: [forbidigo]
      - path: '_test\\.go$'
        linters: [forbidigo]
      # ctx — правило бизнес-кода (conventions.md п. 1): pkg/ и миграции — не бизнес-код
      - path: '(^|/)(pkg/|internal/migrations/)'
        linters: [forbidigo]
      # var-naming — только аббревиатуры (checklist.md п. 34); ALL_CAPS и подчёркивания правило фреймворка не запрещает
      - linters: [revive]
        text: "(don't use ALL_CAPS|don't use an underscore|don't use underscores|should not use underscores)"
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
    out = [HEADER]
    out.append('        domain-no-infra:\n          files:\n            - "**/internal/domains/**"\n          deny:\n')
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
            out.append(f'        {d}-no-other-domains:\n          files:\n            - "**/internal/domains/{d}/**"\n          deny:\n')
            for other in domains:
                if other != d:
                    out.append(deny(f"{mod}/internal/domains/{other}", "чужой домен — через workflow или internal/common"))
    out.append('        workflow-no-workflow:\n          files:\n            - "**/internal/workflows/**"\n'
               '            - "!$test" # внешний тест workflow импортирует сам workflow\n          deny:\n')
    out.append(deny(f"{mod}/internal/workflows", "workflow не вызывает другой workflow"))
    out.append(deny(f"{mod}/internal/infrastructure", "workflow берёт интерфейсы доменов, не инфраструктуру"))
    out.append('        entrypoint-no-infra:\n          files:\n            - "**/internal/entrypoints/**"\n          deny:\n')
    out.append(deny(f"{mod}/internal/infrastructure", "точка входа — через сервис/workflow, инфраструктура собирается в bootstrap"))
    out.append(deny(f"{mod}/internal/app", "точка входа не знает сборку"))
    out.append('        common-no-internal:\n          files:\n            - "**/internal/common/**"\n'
               '            - "!$test" # внешний тестовый пакет импортирует сам common\n          deny:\n')
    for sub in ("domains", "infrastructure", "entrypoints", "workflows", "app"):
        out.append(deny(f"{mod}/internal/{sub}", COMMON_DESC))
    out.append(FOOTER)
    return "".join(out)


def run_go(project: Path, base):
    cfg_dir = CACHE / (project.name + "-" + hashlib.sha1(str(project).encode()).hexdigest()[:8])
    cfg_dir.mkdir(parents=True, exist_ok=True)
    cfg = cfg_dir / "golangci.yml"
    cfg.write_text(render_go(project), encoding="utf-8")
    cmd = ["golangci-lint", "run", "--config", str(cfg)]
    if base:
        cmd += ["--new-from-rev", base]
    print(f"домены (правила «домен не импортирует другой»): {', '.join(domains_of(project)) or 'нет internal/domains'}", flush=True)
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
    return res.returncode


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
    neon = f"""includes:
    - {PHP_TOOLS}/vendor/phpat/phpat/extension.neon
    - {PHP_TOOLS}/vendor/spaze/phpstan-disallowed-calls/extension.neon
    - rules.neon

parameters:
    customRulesetUsed: true
    paths:
        - {cpath}/{sub}
    bootstrapFiles:
        - {cpath}/vendor/autoload.php
    tmpDir: {work}/cache
"""
    docker(container, f"mkdir -p {work}/cache")
    docker(container, f"mkdir -p {PHP_TOOLS}/rules/P{tag}")
    docker(container, f"cat > {PHP_TOOLS}/rules/P{tag}/LayersArchitecture.php", php_rules.render_arch(ns, root, domains))
    docker(container, f"cat > {work}/rules.neon", php_rules.render_neon(ns + "\\LayersArchitecture"))
    docker(container, f"cat > {work}/phpstan.neon", neon)
    files = []
    if base:
        diff = subprocess.check_output(["git", "diff", "--name-only", "--diff-filter=ACMR", base, "--", sub], cwd=project).decode().split()
        # новые файлы задачи ещё не в git — git diff их не показывает, а нарушение в них — тоже нарушение
        diff += subprocess.check_output(["git", "ls-files", "--others", "--exclude-standard", "--", sub], cwd=project).decode().split()
        files = [f"{cpath}/{f}" for f in sorted(set(diff)) if f.endswith(".php")]
        if not files:
            print(f"нет изменённых PHP-файлов в {sub} относительно {base}")
            return 0
    cmd = f"cd {cpath} && php -d memory_limit=2G {PHP_TOOLS}/vendor/bin/phpstan analyse -c {work}/phpstan.neon --no-progress " + " ".join(files)
    print(f"домены (правила «домен не знает другие»): {', '.join(domains) or 'нет ' + sub + '/Domains'}", flush=True)
    print(f"правила: {container}:{work}\n$ {cmd}", flush=True)
    r = docker(container, cmd + " 2>&1")
    sys.stdout.write("\n".join(l for l in r.stdout.splitlines() if "Deprecated: PHP Startup" not in l) + "\n")
    return r.returncode


def main():
    p = argparse.ArgumentParser(description="Механические правила фреймворка на проекте, без файлов в проекте")
    p.add_argument("stack", choices=["go", "php"])
    p.add_argument("project")
    p.add_argument("--base", help="ревизия, от которой смотреть новое (дифф задачи)")
    p.add_argument("--render", action="store_true", help="только напечатать собранный конфиг (go)")
    p.add_argument("--sub", help="php: каталог подпроекта (psr-4 из composer.json проекта), например parking_app")
    p.add_argument("--no-install", action="store_true", help="php: не ставить инструменты (агент ревью — без сети): нет — «не запускался», код 2")
    p.add_argument("--container", default=os.environ.get("PHP_CONTAINER", "php-8.5"), help="php: контейнер с PHP проекта")
    a = p.parse_args()
    project = Path(a.project).expanduser().resolve()
    if a.stack == "php":
        if not a.sub:
            sys.exit("php: нужен --sub <каталог подпроекта>")
        return run_php(project, a.sub, a.container, a.base, a.no_install)
    if a.render:
        sys.stdout.write(render_go(project))
        return 0
    return run_go(project, a.base)


if __name__ == "__main__":
    sys.exit(main())
