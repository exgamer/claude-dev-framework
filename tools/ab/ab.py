#!/usr/bin/env python3
"""A/B фреймворка на написании кода: та же задача на прежней и текущей версии — не стало ли хуже.

Запуск из корня репозитория:
  python3 tools/ab/ab.py prepare --rev <ревизия «до»> [--stack go|php|both]   — рабочая папка, проекты, задача
  python3 tools/ab/ab.py diffs <папка>                                         — слепые диффы для судьи
  python3 tools/ab/ab.py cleanup <папка>                                       — удалить проекты и копии в контейнере

Что делает prepare:
- «до» — `git archive <rev>` во временную папку (`old/`), «после» — рабочее дерево репозитория (`new` = этот репозиторий);
- на стек — два одинаковых проекта из эталона текущей версии (`stacks/go/examples/tariff`, PHP — стенд
  `tools/php-check` в контейнере, он уже с vendor) с задачей `tools/ab/task/<stack>/` в `docs/tasks/archive-tariff/`
  и коммитом `base`; PHP-проекты — в каталоге, смонтированном в PHP-контейнер (`tools/lint/php_container.py`);
- печатает пути, базовые коммиты и поручения агентам (`tools/ab/prompts.md`) с подставленными значениями.
Задача A/B не должна уже быть в эталоне (архивация тарифа: `archived_at`, `/archive`) — иначе смените задачу в `task/`.
Агентов запускает тот, кто проверяет (`skills/dev-rule/SKILL.md`, Шаг 3а): разработчик → ревью той же версии → судья.
"""
import argparse
import json
import os
import random
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools" / "lint"))
import php_container  # noqa: E402

GIT = ["git", "-c", "user.email=ab@local", "-c", "user.name=ab"]
STAND = "/tmp/df-php-check"  # стенд PHP-эталона в контейнере: исходники эталона + vendor (tools/php-check/run.sh)


def sh(cmd, **kw):
    return subprocess.run(cmd, check=True, capture_output=True, text=True, **kw).stdout.strip()


def commit(path: Path, msg: str) -> str:
    sh(GIT + ["add", "-A"], cwd=path)
    sh(GIT + ["commit", "-qm", msg], cwd=path)
    return sh(["git", "rev-parse", "--short", "HEAD"], cwd=path)


def add_task(project: Path, stack: str):
    dst = project / "docs" / "tasks" / "archive-tariff"
    dst.mkdir(parents=True, exist_ok=True)
    for f in (ROOT / "tools" / "ab" / "task" / stack).glob("*.md"):
        shutil.copy(f, dst / f.name)


def mounted_dir(container: str, explicit: str | None) -> tuple[Path, str]:
    """Каталог проектов на хосте, смонтированный в контейнер, и его путь в контейнере. Не любой смонтированный
    каталог (там бывают конфиги — supervisor.d), а тот, где лежат PHP-проекты: с `composer.json` в подкаталогах."""
    mounts = json.loads(sh(["docker", "inspect", container, "--format", "{{json .Mounts}}"]))
    dirs = [(Path(m["Source"]), m["Destination"]) for m in mounts
            if Path(m["Source"]).is_dir() and str(m["Source"]).startswith(str(Path.home()))]
    if explicit:
        pick = [d for d in dirs if d[0] == Path(explicit).expanduser().resolve()]
    else:
        pick = [d for d in dirs if any(d[0].glob("*/composer.json")) or any(d[0].glob("*/*/composer.json"))]
    if len(pick) != 1:
        sys.exit(f"каталог PHP-проектов в контейнере {container} не определён однозначно ({[str(d[0]) for d in dirs]}) — "
                 f"укажите --php-root <каталог хоста>")
    return pick[0]


def prepare(a):
    work = Path(tempfile.mkdtemp(prefix="dev-framework-ab-", dir=Path.home() / ".cache"))
    old = work / "old"
    old.mkdir()
    archive = subprocess.run(["git", "archive", a.rev], cwd=ROOT, capture_output=True, check=True).stdout
    subprocess.run(["tar", "-x", "-C", str(old)], input=archive, check=True)
    state = {"work": str(work), "rev": a.rev, "frameworks": {"old": str(old), "new": str(ROOT)}, "projects": {}}
    stacks = ["go", "php"] if a.stack == "both" else [a.stack]
    if "go" in stacks:
        for v in ("old", "new"):
            p = work / f"go-{v}"
            shutil.copytree(ROOT / "stacks" / "go" / "examples" / "tariff", p)
            sh(["git", "init", "-q"], cwd=p)
            add_task(p, "go")
            state["projects"][f"go-{v}"] = {"path": str(p), "base": commit(p, "base")}
    if "php" in stacks:
        container = php_container.resolve(None, a.container)
        if not container:
            sys.exit(php_container.hint())
        host_root, cont_root = mounted_dir(container, a.php_root)
        tag = time.strftime("%m%d%H%M")
        for v in ("old", "new"):
            name = f"df-ab-{tag}-{v}"
            p, cp = host_root / name, f"{cont_root}/{name}"
            if p.exists():
                sys.exit(f"{p} уже есть")
            sh(["docker", "exec", container, "sh", "-c", f"mkdir -p {cp} && cp -R {STAND}/. {cp}/ && rm -rf {cp}/vendor.prev"])
            (p / ".gitignore").write_text("vendor/\n.phpstan/\n.phpunit.result.cache\n")
            sh(["git", "init", "-q"], cwd=p)
            add_task(p, "php")
            state["projects"][f"php-{v}"] = {"path": str(p), "container_path": cp, "container": container,
                                             "base": commit(p, "base")}
    (work / "state.json").write_text(json.dumps(state, ensure_ascii=False, indent=2))
    print(f"папка A/B: {work}")
    for k, v in state["projects"].items():
        print(f"  {k}: {v['path']} (base {v['base']}{', контейнер ' + v['container'] + ':' + v['container_path'] if 'container' in v else ''})")
    print(f"фреймворк «до»: {old} ({a.rev}); «после»: {ROOT}")
    print(f"поручения агентам — {ROOT / 'tools' / 'ab' / 'prompts.md'}; затем: python3 tools/ab/ab.py diffs {work}")


def diffs(a):
    work = Path(a.work)
    state = json.loads((work / "state.json").read_text())
    out = work / "judge"
    out.mkdir(exist_ok=True)
    mapping = {}
    for stack in ("go", "php"):
        if f"{stack}-old" not in state["projects"]:
            continue
        labels = ["A", "B"]
        random.shuffle(labels)
        for v, label in zip(("old", "new"), labels):
            p = state["projects"][f"{stack}-{v}"]
            paths = ["parking_app", "tests"] if stack == "php" else ["."]
            excl = [] if stack == "php" else [":!docs", ":!review_result"]
            idx = tempfile.mktemp()
            env = dict(os.environ, GIT_INDEX_FILE=idx)
            subprocess.run(["git", "read-tree", p["base"]], cwd=p["path"], env=env, check=True)
            subprocess.run(["git", "add", "-A", "--"] + paths + excl, cwd=p["path"], env=env, check=True, capture_output=True)
            d = subprocess.run(["git", "diff", "--cached", p["base"], "--"] + paths + excl, cwd=p["path"], env=env,
                               capture_output=True, text=True, check=True).stdout
            os.remove(idx)
            (out / f"{stack}-{label}.diff").write_text(d)
            mapping[f"{stack}-{label}"] = v
    (work / "mapping.secret.json").write_text(json.dumps(mapping, indent=2))
    print(f"диффы для судьи: {out} (какая версия под какой буквой — {work / 'mapping.secret.json'}, судье не показывать)")


def cleanup(a):
    work = Path(a.work)
    state = json.loads((work / "state.json").read_text())
    for k, p in state["projects"].items():
        if "container" in p:
            subprocess.run(["docker", "exec", "-u", "root", p["container"], "rm", "-rf", p["container_path"]], check=False)
            subprocess.run(["docker", "exec", p["container"], "sh", "-c",
                            f"rm -rf /tmp/df-lint/{Path(p['path']).name}-*"], check=False)
        shutil.rmtree(p["path"], ignore_errors=True)
    store = ROOT / "tools" / "lint" / ".php-containers.json"
    if store.exists():
        data = json.loads(store.read_text())
        data = {k: v for k, v in data.items() if "/df-ab-" not in k}
        store.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n")
    keep = work / "judge"
    print(f"проекты удалены; отчёты судьи и ревью — скопируйте из {keep} и проектов до cleanup, если нужны; папка {work} оставлена")


def main():
    p = argparse.ArgumentParser(description="A/B фреймворка на написании кода")
    sub = p.add_subparsers(dest="cmd", required=True)
    pr = sub.add_parser("prepare")
    pr.add_argument("--rev", required=True, help="ревизия фреймворка «до» (обычно HEAD или прошлый коммит)")
    pr.add_argument("--stack", choices=["go", "php", "both"], default="both")
    pr.add_argument("--container", help="PHP-контейнер (по умолчанию — tools/lint/php_container.py)")
    pr.add_argument("--php-root", help="каталог хоста с PHP-проектами, смонтированный в контейнер (если не определяется сам)")
    for name in ("diffs", "cleanup"):
        sp = sub.add_parser(name)
        sp.add_argument("work", help="папка A/B из prepare")
    a = p.parse_args()
    {"prepare": prepare, "diffs": diffs, "cleanup": cleanup}[a.cmd](a)


if __name__ == "__main__":
    main()
