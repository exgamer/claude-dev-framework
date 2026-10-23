#!/usr/bin/env python3
"""Ревью «до/после» на реальном MR: правка ревью на прежней и текущей версии фреймворка — не стало ли хуже на чужом коде.

Эталоны чисты по построению: шум нового правила ревью (ложные находки на легаси, монорепозитории, чужих именах) и
потерю находок видит только реальный MR. Правило — `skills/dev-rule/SKILL.md`, Шаг 3а, п. 5.

Проекты — локальный список `tools/lint/.real-projects.json` (`tools/lint/real_projects.py add`), в фреймворке путей нет.
Запуск из корня репозитория:
  python3 tools/ab/real_review.py candidates [--grep <regex>] [-n N]       — merge-коммиты проектов из списка: размер,
                                                                            файлы под regex (выбор MR с поверхностью правки)
  python3 tools/ab/real_review.py prepare --project <путь> --mr <merge-коммит> --rev <ревизия «до»> [--sub X]
  python3 tools/ab/real_review.py prepare --project <путь> --base <rev> --tip <rev> --rev <ревизия «до»> [--sub X]
  python3 tools/ab/real_review.py judge <папка>                             — отчёты ревью под буквами A/B для судьи
  python3 tools/ab/real_review.py cleanup <папка>                           — удалить копии MR (отчёты судьи остаются)

Что делает prepare: «до» — `git archive <rev>` фреймворка в `old/`, «после» — рабочее дерево репозитория; три копии MR
(`mr-old`, `mr-new` — ревьюерам; судье — `code/` в отдельной папке, без отчётов ревью и соответствия букв): `git archive <base>` проекта, коммит `base`,
дифф `<base>..<tip>` — незакоммиченным; печатает поручения (`tools/ab/prompts.md`, «Ревью на реальном MR»). Проект
только читается: состояние git (HEAD, отслеживаемые и неотслеживаемые файлы, дифф рабочего дерева; игнорируемые файлы
git не видит) сверяется до и после; `judge` сверяет ещё раз. `judge` кладёт последний отчёт каждого ревьюера под буквой
A/B, пути копий и фреймворков в нём заменены на `{P}`/`{DF}` — по тексту версию не узнать; повторный `judge` буквы не
меняет. Копии — без vendor и вне PHP-контейнера: анализаторы в них не запускаются (у «до» и «после» одинаково).
Коды: 0 — готово; 1 — проект изменился или копия не собралась; 2 — неверные аргументы.
"""
import argparse
import json
import random
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools" / "lint"))
import real_projects  # noqa: E402

GIT = ["git", "-c", "user.email=rr@local", "-c", "user.name=rr"]


def sh(cmd, **kw) -> str:
    return subprocess.run(cmd, check=True, capture_output=True, text=True, **kw).stdout.strip()


def known(path: str, sub: str | None) -> dict | None:
    """Запись списка: по пути и подпроекту; подпроекта MR нет в списке — стек по любой записи того же пути."""
    root = str(Path(path).expanduser().resolve())
    same = [p for p in real_projects.load() if p["path"] == root]
    exact = [p for p in same if sub is None or p.get("sub") == sub]
    if len(exact) == 1:
        return exact[0]
    stacks = {p["stack"] for p in same}
    return {"stack": stacks.pop()} if len(stacks) == 1 else None


def candidates(a) -> int:
    projects = real_projects.load()
    if not projects:
        print("список проектов пуст: python3 tools/lint/real_projects.py add <go|php> <путь> [--sub X]", file=sys.stderr)
        return 2
    if a.n < 1:
        print("-n: N ≥ 1", file=sys.stderr)
        return 2
    pat = re.compile(a.grep, re.I) if a.grep else None
    for root in dict.fromkeys(p["path"] for p in projects):
        print(f"== {root}")
        merges = real_projects.git(Path(root), "log", "--first-parent", "--merges", "--format=%h %ad %s",
                                   "--date=short", f"-{a.n}").stdout.splitlines()
        for line in merges:
            rev = line.split()[0]
            if re.search(r"Merge (remote-tracking )?branch '[^']*' of ", line):
                continue  # обратное слияние «Merge branch 'X' of <remote> into …» — не MR
            files = real_projects.git(Path(root), "diff", "--name-only", f"{rev}^1", rev).stdout.splitlines()
            hits = [f for f in files if pat.search(f)] if pat else []
            if pat and not hits:
                continue
            stat = real_projects.git(Path(root), "diff", "--shortstat", f"{rev}^1", rev).stdout.strip()
            print(f"  {line[:90]} | {stat}" + (f" | {len(hits)}: {' '.join(hits[:3])}" if hits else ""))
    return 0


def copy(project: Path, base: str, tip: str, dst: Path) -> str:
    dst.mkdir(parents=True)
    archive = subprocess.run(["git", "archive", base], cwd=project, capture_output=True, check=True).stdout
    subprocess.run(["tar", "-x", "-C", str(dst)], input=archive, check=True)
    sh(["git", "init", "-q"], cwd=dst)
    sh(GIT + ["add", "-A"], cwd=dst)
    sh(GIT + ["commit", "-qm", "base"], cwd=dst)
    diff = subprocess.run(["git", "diff", "--binary", base, tip], cwd=project, capture_output=True, check=True).stdout
    # --index: новые файлы MR — в индексе, иначе `git diff HEAD` ревьюера их не видит
    subprocess.run(["git", "apply", "--index"], cwd=dst, input=diff, check=True)
    return sh(["git", "rev-parse", "--short", "HEAD"], cwd=dst)


def prepare(a) -> int:
    project = Path(a.project).expanduser().resolve()
    if real_projects.git(project, "rev-parse", "--git-dir").returncode:
        print(f"{project} — не репозиторий git", file=sys.stderr)
        return 2
    entry = known(str(project), a.sub)
    stack = a.stack or (entry or {}).get("stack")
    sub = a.sub or (entry or {}).get("sub")
    if stack not in ("go", "php"):
        print("стек не определён: проекта (или его подпроекта --sub) нет в tools/lint/.real-projects.json, "
              "или у пути записи разных стеков — укажите --stack go|php", file=sys.stderr)
        return 2
    if a.mr:
        if real_projects.git(project, "rev-parse", "--verify", "--quiet", f"{a.mr}^2").returncode:
            print(f"--mr {a.mr}: не merge-коммит (или нет такой ревизии) — для squash укажите --base и --tip", file=sys.stderr)
            return 2
        base, tip = f"{a.mr}^1", a.mr
    else:
        base, tip = a.base, a.tip
    if not (base and tip):
        print("нужен --mr <merge-коммит> или --base и --tip", file=sys.stderr)
        return 2
    for rev in (base, tip):
        if real_projects.git(project, "rev-parse", "--verify", "--quiet", f"{rev}^{{commit}}").returncode:
            print(f"ревизии {rev} нет в {project}", file=sys.stderr)
            return 2
    if real_projects.git(ROOT, "rev-parse", "--verify", "--quiet", f"{a.rev}^{{commit}}").returncode:
        print(f"--rev {a.rev}: нет такой ревизии фреймворка", file=sys.stderr)
        return 2
    # тема и первая строка тела самого merge-коммита (у GitLab в теле — название MR)
    title = " — ".join(x for x in real_projects.git(project, "log", "-1", "--format=%s%n%b", tip).stdout.strip()
                       .splitlines()[:3] if x.strip())
    before = real_projects.snapshot(project)
    cache = Path.home() / ".cache"
    cache.mkdir(exist_ok=True)
    work = Path(tempfile.mkdtemp(prefix="dev-framework-rr-", dir=cache))
    # судье — отдельная папка: в work лежат mapping.secret.json и копии ревьюеров с их отчётами
    jdir = Path(tempfile.mkdtemp(prefix="dev-framework-rrj-", dir=cache))
    old = work / "old"
    copies = {"old": str(work / "mr-old"), "new": str(work / "mr-new"), "judge": str(jdir / "code")}
    try:
        old.mkdir()
        archive = subprocess.run(["git", "archive", a.rev], cwd=ROOT, capture_output=True, check=True).stdout
        subprocess.run(["tar", "-x", "-C", str(old)], input=archive, check=True)
        for p in copies.values():
            copy(project, base, tip, Path(p))
    except subprocess.CalledProcessError as e:
        shutil.rmtree(work, ignore_errors=True)
        shutil.rmtree(jdir, ignore_errors=True)
        err = e.stderr.decode(errors="replace") if isinstance(e.stderr, bytes) else (e.stderr or "")
        print(f"копия не собралась ({' '.join(map(str, e.cmd))}): {err.strip()[:300]}", file=sys.stderr)
        return 1
    if real_projects.snapshot(project) != before:
        shutil.rmtree(work, ignore_errors=True)
        shutil.rmtree(jdir, ignore_errors=True)
        print(f"ОШИБКА: проект {project} изменился во время подготовки (копии удалены)", file=sys.stderr)
        return 1
    state = {"work": str(work), "rev": a.rev, "project": str(project), "snapshot": before, "stack": stack, "sub": sub,
             "base": base, "tip": tip, "title": title, "frameworks": {"old": str(old), "new": str(ROOT)}, "copies": copies, "judge": str(jdir)}
    (work / "state.json").write_text(json.dumps(state, ensure_ascii=False, indent=2))
    stack_text = "Go (Go SDK)" if stack == "go" else "PHP (Laravel + mps/core)" + (f", подпроект `{sub}`" if sub else "")
    print(f"папка: {work}\nMR: {title} ({base}..{tip}), стек: {stack_text}")
    print(f"фреймворк «до»: {old} ({a.rev}); «после»: {ROOT}")
    for v in ("old", "new"):
        print(f"  ревью-{v}: {{DF}} = {state['frameworks'][v]}, {{P}} = {copies[v]}")
    print(f"  судья: {{DF}} = {ROOT}, {{P}} = {copies['judge']}, {{J}} = {jdir}")
    print(f"поручения — {ROOT / 'tools' / 'ab' / 'prompts.md'}, «Ревью на реальном MR»; затем: python3 tools/ab/real_review.py judge {work}")
    return 0


def load_state(work: Path) -> dict | None:
    try:
        return json.loads((work / "state.json").read_text())
    except (OSError, ValueError):
        print(f"{work}: нет state.json — не папка из prepare", file=sys.stderr)
        return None


def judge(a) -> int:
    work = Path(a.work)
    state = load_state(work)
    if state is None:
        return 2
    out = Path(state["judge"])
    secret = work / "mapping.secret.json"
    if secret.exists():  # повторный judge — те же буквы
        mapping = json.loads(secret.read_text())
    else:
        labels = ["A", "B"]
        random.shuffle(labels)
        mapping = dict(zip(labels, ("old", "new")))
    # пути копий и фреймворков в отчёте выдали бы версию — заменяются на заглушки, длинные раньше коротких
    home = str(Path.home())
    masks = [(state["copies"][v], "{P}") for v in ("old", "new")] + \
            [(state["frameworks"][v], "{DF}") for v in ("old", "new")]
    masks += [(src.replace(home, "~", 1), dst) for src, dst in masks if src.startswith(home)]
    masks.sort(key=lambda m: -len(m[0]))
    for label, v in mapping.items():
        reports = sorted((Path(state["copies"][v]) / "review_result").glob("*.md"))
        if not reports:
            print(f"нет отчёта ревью в {state['copies'][v]}/review_result", file=sys.stderr)
            return 2
        text = reports[-1].read_text(encoding="utf-8")
        for src, dst in masks:
            text = text.replace(src, dst)
        text = re.sub(r"\bmr-(old|new)\b", "{P}", text)
        text = re.sub(r"(\.\./)+old\b", "{DF}", text)
        (out / f"{label}.md").write_text(text, encoding="utf-8")
    secret.write_text(json.dumps(mapping, indent=2))
    changed = real_projects.snapshot(Path(state["project"])) != state["snapshot"]
    print(f"судье: {{J}} = {out} (A.md, B.md, код MR — code/, дифф к HEAD); "
          f"какая буква какая версия — {work / 'mapping.secret.json'}, судье не показывать")
    if changed:
        print(f"ОШИБКА: проект {state['project']} изменился после prepare — разобрать, кто его тронул", file=sys.stderr)
        return 1
    print("проект не изменён (git status и дифф рабочего дерева совпадают с prepare)")
    return 0


def cleanup(a) -> int:
    work = Path(a.work)
    state = load_state(work)
    if state is None:
        return 2
    for p in state["copies"].values():
        shutil.rmtree(p, ignore_errors=True)
    shutil.rmtree(work / "old", ignore_errors=True)
    print(f"копии удалены; отчёты и вердикт судьи — {state['judge']}, папка {work} оставлена")
    return 0


def main() -> int:
    ap = argparse.ArgumentParser(description="Ревью «до/после» на реальном MR")
    sp = ap.add_subparsers(dest="cmd", required=True)
    c = sp.add_parser("candidates", help="merge-коммиты проектов из списка")
    c.add_argument("--grep", help="regex по путям файлов MR (поверхность правки)")
    c.add_argument("-n", type=int, default=200, help="сколько merge-коммитов смотреть в проекте (по умолчанию 200)")
    p = sp.add_parser("prepare", help="копии MR и фреймворк «до»")
    p.add_argument("--project", required=True, help="путь к проекту (из tools/lint/.real-projects.json или со --stack)")
    p.add_argument("--mr", help="merge-коммит MR: база — его первый родитель")
    p.add_argument("--base", help="база MR (вместо --mr)")
    p.add_argument("--tip", help="конец MR (вместо --mr)")
    p.add_argument("--rev", required=True, help="ревизия фреймворка «до» (коммит до правки)")
    p.add_argument("--stack", choices=["go", "php"], help="если проекта нет в списке")
    p.add_argument("--sub", help="php: подпроект (если в списке их несколько у проекта)")
    for name, text in (("judge", "отчёты ревью под буквами A/B для судьи"), ("cleanup", "удалить копии MR")):
        s = sp.add_parser(name, help=text)
        s.add_argument("work", help="папка из prepare")
    a = ap.parse_args()
    return {"candidates": candidates, "prepare": prepare, "judge": judge, "cleanup": cleanup}[a.cmd](a)


if __name__ == "__main__":
    sys.exit(main())
