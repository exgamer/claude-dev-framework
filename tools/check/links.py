#!/usr/bin/env python3
"""Ссылки фреймворка: битые пути и орфаны (md-файлы, до которых не дойти от skills/, agents/, README.md).

Запуск из корня репозитория: python3 tools/check/links.py
Код возврата: 0 — чисто, 1 — есть битые ссылки или орфаны.

Путь в тексте разрешается так же, как его читает роль: от папки файла, от корня ({DF}/ и ~/.claude/dev-framework/
отбрасываются), от stacks/go, stacks/php, stacks, skills (сокращения go-sdk/…, mps-core/…, dev-go/SKILL.md).
{a,b} раскрывается в оба варианта. Не проверяются: core/decisions-log.md (история), справочники official/ и
reference/ (копии чужой документации), пути проекта (docs/, .claude/, internal/ и т. п.) и шаблоны из IGNORE.
"""
import itertools
import os
import re
import subprocess
import sys

ENTRY_DIRS = ("skills/", "agents/")
ENTRY_FILES = ("README.md",)
BASES = ("", "stacks/go", "stacks/php", "stacks", "skills")
SKIP_SOURCES = ("core/decisions-log.md", "CHANGELOG.md")  # история: упоминает и удалённые файлы
SKIP_SOURCE_PARTS = ("/official/", "/reference/")
# Пути внутри проекта пользователя или вне фреймворка — их в репозитории нет по определению
PROJECT_PREFIXES = ("docs/", ".claude/", "internal/", "cmd/", "pkg/", "parking_app/", "app/", "App/", "review_result/",
                    "backend-regulation/", "graphify-out/", "corpus/", "vendor/", "-review/")
IGNORE = re.compile(r"<[^>]+>|\.\.\.|…")

PATH_RE = re.compile(r"(?:\{DF\}/|~/\.claude/dev-framework/)?(?:[\w.\-{},]+/)+[\w.\-{},]*\.md|(?:\{DF\}/)?[\w.\-]+\.md")


def repo_files():
    out = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard"]).decode()
    # Удалённый, но ещё не закоммиченный файл git ls-files продолжает отдавать — его нет на диске
    return [f for f in out.split("\n") if f and os.path.isfile(f)
            and not f.startswith(("tools/graph/graphify-out/", "tools/graph/cache/", "tools/graph/corpus/"))]


def expand(token):
    """{gitlab,github}/x.md → gitlab/x.md, github/x.md"""
    parts = re.split(r"\{([^{}]*,[^{}]*)\}", token)
    if len(parts) == 1:
        return [token]
    fixed, choices = parts[0::2], [p.split(",") for p in parts[1::2]]
    return ["".join(x for pair in itertools.zip_longest(fixed, combo, fillvalue="") for x in pair)
            for combo in itertools.product(*choices)]


def resolve(src, token, known):
    for prefix in ("{DF}/", "~/.claude/dev-framework/"):
        if token.startswith(prefix):
            token = token[len(prefix):]
    bases = [os.path.dirname(src)] + list(BASES)
    parts = src.split("/")
    bases += ["/".join(parts[:i]) for i in range(len(parts) - 1, 0, -1)]
    for base in bases:
        cand = os.path.normpath(os.path.join(base, token))
        if cand in known:
            return cand
    return None


def stack_near(src, line="", pos=0):
    """Стек ссылки: ближайшая метка «PHP»/«Go» перед ней в строке, иначе стек документа."""
    labels = re.findall(r"\b(PHP|Go)\s*(?:—|:)", line[:pos])  # явная метка «PHP —» / «Go:» перед ссылкой
    if labels:
        return labels[-1].lower()
    parts = src.split("/")
    if parts[0] == "stacks" and len(parts) > 1:
        return parts[1]
    if src.startswith(("skills/dev-go", "agents/df-go")):
        return "go"
    if src.startswith(("skills/dev-php", "agents/df-php")):
        return "php"
    return None


def resolve_loose(src, token, known, line="", pos=0):
    """resolve + голое имя или хвост пути (`subagent.md`, `go-sdk/app.md`): единственный файл с таким хвостом;
    несколько — по стеку ссылки (stack_near); иначе неоднозначно — None."""
    bare = token
    for prefix in ("{DF}/", "~/.claude/dev-framework/"):
        bare = bare.removeprefix(prefix)
    near = os.path.normpath(os.path.join(os.path.dirname(src), bare))
    if near in known:
        return near
    same = sorted(f for f in known if f == bare or f.endswith("/" + bare))
    if len(same) > 1 and not token.startswith(("{DF}/", "~/")):  # одно имя в двух стеках: base-пути resolve выбрали бы Go
        stack = stack_near(src, line, pos)
        pick = [f for f in same if stack and f.startswith(f"stacks/{stack}/")]
        return pick[0] if len(pick) == 1 else None
    return resolve(src, token, known) or (same[0] if len(same) == 1 else None)


def main():
    files = repo_files()
    known = set(files)
    docs = [f for f in files if f.endswith(".md")]
    edges, broken = {}, []

    for doc in docs:
        text = open(doc, encoding="utf-8", errors="ignore").read()
        targets = set()
        check = doc not in SKIP_SOURCES and not any(p in doc for p in SKIP_SOURCE_PARTS)
        for line_no, line in enumerate(text.split("\n"), 1):
            for match in PATH_RE.finditer(line):
                token = match.group(0)
                if IGNORE.search(token) or token.startswith(("http", "//")):
                    continue
                variants = expand(token)
                resolved = [resolve(doc, v, known) for v in variants]
                targets.update(r for r in resolved if r)
                lost = [v for v, r in zip(variants, resolved) if r is None and "/" in v]
                lost = [v for v in lost if not v.removeprefix("./").startswith(PROJECT_PREFIXES)]
                if check and lost:
                    broken.append(f"{doc}:{line_no} → {', '.join(lost)}")
        edges[doc] = targets

    seen, stack = set(), [d for d in docs if d.startswith(ENTRY_DIRS)] + list(ENTRY_FILES)
    while stack:
        cur = stack.pop()
        if cur in seen:
            continue
        seen.add(cur)
        stack.extend(t for t in edges.get(cur, ()) if t not in seen)

    # Справочники official/ и reference/ — данные: считаются подключёнными, если подключён хоть один файл их папки
    reached_dirs = {os.path.dirname(f) for f in seen}
    orphans = [d for d in docs if d not in seen and not (any(p in d for p in SKIP_SOURCE_PARTS)
                                                          and os.path.dirname(d) in reached_dirs)]
    orphans = [d for d in orphans if not any(p in d for p in SKIP_SOURCE_PARTS) or os.path.dirname(d) not in reached_dirs]

    print(f"битые ссылки: {len(broken)}")
    for b in broken:
        print(f"  {b}")
    print(f"орфаны: {len(orphans)}")
    for o in sorted(orphans):
        print(f"  {o}")
    return 1 if broken or orphans else 0


if __name__ == "__main__":
    sys.exit(main())
