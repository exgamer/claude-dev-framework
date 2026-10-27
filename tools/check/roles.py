#!/usr/bin/env python3
"""Цепочки загрузки ролей: что каждая роль читает напрямую и что изменилось относительно ревизии.

Запуск из корня репозитория: python3 tools/check/roles.py [--rev HEAD] [--show]
Код возврата: 0 — ни одна роль не потеряла файл; 1 — роль потеряла файл, до которого больше не дотягивается
и через одну ссылку (вероятная потеря инструкции).

Роль — `skills/*/SKILL.md` и `agents/df-*.md`. Прямая загрузка — md-файлы фреймворка, на которые роль ссылается
(у агента — ещё прямая загрузка его skill, если агент на него ссылается). Транзитивно любая роль дотягивается почти до
всего фреймворка, поэтому сравнивается прямая загрузка: файл пропал из неё и не достижим через одну ссылку —
«потеряно»; достижим через одну ссылку — «перенесён глубже» (проверить глазами, что роль его по-прежнему читает).
Ссылка — полный путь, голое имя или хвост пути (`subagent.md`, `go-sdk/app.md`; одно имя в двух стеках — по метке «PHP —»/«Go —»
или стеку роли, у роли без стека — файл обоих стеков), маска (`architecture/*.md` — все файлы папки). Не загрузка — упоминание
после «кроме», «не для», с «не читаешь»/«не читается» в том же обороте и перечень поводов «если правка задевает …» до конца
предложения. Команды роли (`lint.py`, `run.sh`) скрипт не видит.
Справочники `official/`/`reference/` и `core/decisions-log.md` не считаются; `<stack>` в пути раскрывается в оба стека; ссылка skill → его агент — не загрузка. Механическая часть проверки
поведения «до/после» (`skills/dev-rule/SKILL.md`, Шаг 3а): содержание правил по-прежнему сверяет агент.
"""
import argparse
import os
import re
import subprocess
import sys
import tempfile

sys.path.insert(0, os.path.dirname(__file__))
import links  # noqa: E402

SKIP = ("/official/", "/reference/", "core/decisions-log.md", "CHANGELOG.md")
MASK_RE = re.compile(r"(?:\{DF\}/|~/\.claude/dev-framework/)?((?:[\w.\-{},]+/)+)\*\.md")
EXCEPT_RE = re.compile(r"кроме\s+(?:`[^`]+`(?:\s*(?:,|и)\s*)?)+")  # «кроме `a.md` и `b.md`»
TRIGGER_RE = re.compile(r"если правка задевает.*?(?=\.\s+[А-ЯA-Z]|$)")  # перечень поводов, не загрузка
NOT_READ_AFTER = re.compile(r"^[^;.(`]*не\s+чита")  # «`x.md` — материал ревьюера, целиком не читаешь»
NOT_FOR_BEFORE = re.compile(r"не\s+для[^;.(]*$")


def skipped(line, start, end):
    """Упоминание файла, которое не загрузка: перечень поводов, «кроме …», «не для …», «… не читаешь»."""
    for rx in (TRIGGER_RE, EXCEPT_RE):
        if any(m.start() <= start < m.end() for m in rx.finditer(line)):
            return True
    return bool(NOT_READ_AFTER.search(line[end:].lstrip("`")) or NOT_FOR_BEFORE.search(line[:start]))


def resolve_all(d, tok, known, line, pos):
    """links.resolve_loose; голое имя, которое есть в обоих стеках, у роли без стека — файлы обоих стеков."""
    r = links.resolve_loose(d, tok, known, line, pos)
    if r:
        return [r]
    bare = tok.removeprefix("{DF}/").removeprefix("~/.claude/dev-framework/")
    if "/" in bare or links.stack_near(d, line, pos):
        return []
    return sorted(f for f in known if f.startswith(("stacks/go/", "stacks/php/")) and f.endswith("/" + bare)
                  and not any(s in f for s in SKIP))


def mask_files(d, mask_dir, known):
    """`architecture/*.md` → файлы папки (без вложенных); папка — как путь в links.resolve."""
    for base in [os.path.dirname(d)] + list(links.BASES):
        folder = os.path.normpath(os.path.join(base, mask_dir))
        files = [f for f in known if os.path.dirname(f) == folder and f.endswith(".md")]
        if files:
            return files
    return []


def graph(root):
    cwd = os.getcwd()
    os.chdir(root)
    try:
        files = links.repo_files() if os.path.isdir(".git") else [
            os.path.relpath(os.path.join(d, f), ".") for d, _, fs in os.walk(".") for f in fs]
        files = [f.removeprefix("./") for f in files]
        known = set(files)
        docs = [f for f in files if f.endswith(".md") and not f.startswith("tools/graph/")]
        edges = {}
        for d in docs:
            targets = set()
            for line in open(d, encoding="utf-8", errors="ignore"):
                line = line.replace("<stack>", "{go,php}")  # роль читает файл своего стека: stacks/<stack>/…, dev-<stack>
                found, excluded = set(), set()
                for m in MASK_RE.finditer(line):
                    if not skipped(line, m.start(), m.end()):
                        for v in links.expand(m.group(1)):
                            found.update(mask_files(d, v.removeprefix("{DF}/"), known))
                for m in links.PATH_RE.finditer(line):
                    tok = m.group(0)
                    if links.IGNORE.search(tok):
                        continue
                    resolved = [r for v in links.expand(tok) for r in resolve_all(d, v, known, line, m.start())]
                    (excluded if skipped(line, m.start(), m.end()) else found).update(resolved)
                targets |= {r for r in found - excluded
                            if r.endswith(".md") and r != d and not any(s in r for s in SKIP)}
            edges[d] = targets
        return edges
    finally:
        os.chdir(cwd)


def roles(edges):
    return sorted(r for r in edges if (r.startswith("skills/") and r.endswith("/SKILL.md")) or
                  (r.startswith("agents/df-") and r.endswith(".md")))


def direct(edges, role):
    out = set(edges.get(role, set()))
    for t in list(out):  # агент → его skill: агент выполняет шаги skill
        if role.startswith("agents/") and t.startswith("skills/") and t.endswith("/SKILL.md"):
            out |= edges.get(t, set())
    out.discard(role)
    if role.startswith("skills/"):  # skill не читает файл своего агента — агент читает skill
        out = {t for t in out if not t.startswith("agents/")}
    return out


def depth2(edges, role):
    d1 = direct(edges, role)
    out = set(d1)
    for f in d1:
        out |= edges.get(f, set())
    return out


def tree_at(rev):
    tmp = tempfile.mkdtemp(prefix="roles-")
    archive = subprocess.run(["git", "archive", rev], capture_output=True, check=True).stdout
    subprocess.run(["tar", "-x", "-C", tmp], input=archive, check=True)
    return tmp


def main():
    p = argparse.ArgumentParser(description="Цепочки загрузки ролей и их разница с ревизией")
    p.add_argument("--rev", default="HEAD", help="с какой ревизией сравнивать (по умолчанию HEAD)")
    p.add_argument("--show", action="store_true", help="напечатать прямую загрузку каждой роли")
    p.add_argument("--changed", action="store_true", help="роли, которые напрямую читают файлы, изменённые относительно --rev")
    a = p.parse_args()
    now = graph(".")
    old_root = tree_at(a.rev)
    old = graph(old_root)
    lost_total = 0
    for role in sorted(set(roles(now)) | set(roles(old))):
        if role not in now:
            print(f"{role}: роли больше нет (было в {a.rev})")
            lost_total += 1
            continue
        d_now, d_old = direct(now, role), direct(old, role) if role in old else set()
        if a.show:
            print(f"{role}: {len(d_now)} файлов — " + ", ".join(sorted(d_now)))
        removed, added = sorted(d_old - d_now), sorted(d_now - d_old)
        if not removed and not added:
            continue
        deep = depth2(now, role)
        lost = [f for f in removed if f not in deep]
        moved = [f for f in removed if f in deep]
        lost_total += len(lost)
        print(f"{role}:")
        for f in lost:
            print(f"  ПОТЕРЯН   {f} (не достижим и через одну ссылку)")
        for f in moved:
            print(f"  ГЛУБЖЕ    {f} (не напрямую, через одну ссылку — проверить, что роль читает)")
        for f in added:
            print(f"  добавлен  {f}")
    if a.changed:
        diff = subprocess.run(["git", "diff", "--name-only", a.rev, "--", "*.md"], capture_output=True, text=True).stdout.split()
        diff += subprocess.run(["git", "ls-files", "--others", "--exclude-standard", "--", "*.md"], capture_output=True, text=True).stdout.split()
        for f in sorted(set(diff)):
            readers = [r for r in roles(now) if f in direct(now, r) or f == r]
            print(f"изменён {f}: читают {', '.join(readers) or 'ни одна роль напрямую'}")
    print(f"ролей: {len(roles(now))}; потеряно файлов: {lost_total} (относительно {a.rev})")
    subprocess.run(["rm", "-rf", old_root])
    return 1 if lost_total else 0


if __name__ == "__main__":
    sys.exit(main())
