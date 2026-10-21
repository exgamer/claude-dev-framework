#!/usr/bin/env python3
"""Глушение проверок в диффе (`core/principles.md`, «Честность результата»; тег `check-silenced`) — разбором текста.

    python3 tools/lint/diff_silence.py <проект> --base REV [--sub <подпроект>]

Смотрит только **добавленные строки** диффа относительно ревизии (`git diff`) и новые файлы задачи (не в git — целиком).
Без `--base` не запускается: полный прогон — не дифф, старые заглушки проекта — не находка задачи. Вызывается из
`tools/lint/lint.py` при `--base` (оба стека). С `--sub` (PHP-монорепозиторий) — только файлы подпроекта и конфиги
анализатора (`phpstan*.neon*`) в корне проекта и подпроекта.

Правила (имя — в `tools/check/lint-proof/RULES.md`, колонка «Правило конфига»):
- `silence:go` — `//nolint` (нужен вид `//nolint:<линтер> // причина`), `t.Skip`/`Skipf`/`SkipNow` в `*_test.go` без
  причины (сообщение-строка `t.Skip("…")` со словом — причина; `t.SkipNow()`, `t.Skip()` — нет); в `.golangci.yml`/`.golangci.yaml` — новые записи в `disable`, `exclusions`, `exclude*`, `skip-dirs`/`skip-files`,
  `disabled-checks`, `disable-all: true`, `disabled: true`;
- `silence:php` — `@phpstan-ignore*` (причина — `(…)` после идентификатора или комментарий; идентификатор ошибки
  `argument.type` — не причина), `markTestSkipped`/
  `markTestIncomplete` без причины (сообщение-строка со словом — причина; `markTestSkipped()`, `('')` — нет),
  `#[CodeCoverageIgnore]`/`@codeCoverageIgnore*`; в `phpstan*.neon*` — понижение `level`
  относительно ревизии, новые записи в `ignoreErrors`, `excludePaths`/`excludes_analyse`.
Причина — комментарий с текстом на той же строке или на предыдущей непустой строке (строка-тег docblock — `* @author …`,
`* @return …` — не причина); у записи конфига — ещё у её
родителя (элемент списка, ключ блока), если он тоже добавлен в диффе. Код возврата: 0 — чисто, 1 — находки, 2 — git не выполнился.
"""
import argparse
import re
import subprocess
import sys
from pathlib import Path

TAG = "check-silenced"
WORD = re.compile(r"[^\W\d_]{3,}")  # слово причины: 3+ буквы
NOLINT = re.compile(r"//\s*nolint\b")
NOLINT_OK = re.compile(r"//nolint:[\w,-]+\s*//\s*(.*)$")
GO_SKIP = re.compile(r"\b\w+\.(?:Skip|Skipf|SkipNow)\(")
PHPSTAN_IGNORE = re.compile(r"@phpstan-ignore[\w-]*")
# маркер вместе с идентификаторами ошибок (`argument.type, method.notFound`) — идентификатор не причина
PHPSTAN_MARKER = re.compile(r"@phpstan-ignore[\w-]*(?:\s+[A-Za-z]\w*(?:\.\w+)+(?:\s*,\s*[A-Za-z]\w*(?:\.\w+)+)*)?")
PHPSTAN_REASON = re.compile(r"@phpstan-ignore[\w-]*(?:\s+[\w.,\s]+?)?\s*\(([^)]*)\)")
PHP_SKIP = re.compile(r"\bmarkTest(?:Skipped|Incomplete)\s*\(")
# первый аргумент пропуска теста — строковый литерал (сообщение): `t.Skip("…")`, `markTestSkipped('…')`, именованный
# `markTestSkipped(message: '…')`, формат `sprintf('…', …)`/`fmt.Sprintf("…", …)`
SKIP_MESSAGE = re.compile(r"(?:\b\w+\.(?:Skip|Skipf)|\bmarkTest(?:Skipped|Incomplete))\s*\(\s*(?:message\s*:\s*)?"
                          r"(?:(?:fmt\.)?[Ss]printf\s*\(\s*)?(['\"`])((?:\\.|(?!\1).)*)\1", re.S)
PHP_COVERAGE = re.compile(r"#\[\s*\\?(?:[\w\\]*\\)?CodeCoverageIgnore\b|@codeCoverageIgnore\w*")
# Ключи конфигов, запись в которых отключает проверку
GOLANGCI_KEYS = {"disable", "disabled-checks", "exclusions", "exclude", "exclude-rules", "exclude-dirs", "exclude-files",
                 "exclude-functions", "skip-dirs", "skip-files"}
GOLANGCI_FLAGS = {"disable-all", "disabled"}  # со значением true
NEON_KEYS = {"ignoreErrors", "excludePaths", "excludes_analyse"}
KEY = re.compile(r"^\s*(?:-\s+)?([\w.-]+)\s*:(?:\s|$)")
# строка docblock, которая начинается с тега (`* @author …`, `* @return int`), — чужой тег, не причина
DOC_TAG = re.compile(r"^(?:/\*\*|\*)\s*@\w")


def git(project: Path, *args):
    return subprocess.run(["git", *args], cwd=project, capture_output=True, text=True)


def added_lines(project: Path, base: str):
    """{файл: множество номеров добавленных строк} — дифф с ревизией и новые файлы (не в git) целиком; пути — от `project`."""
    d = git(project, "diff", "-U0", "--no-color", "--no-ext-diff", "--relative", "--diff-filter=ACMR", base, "--", ".")
    if d.returncode:
        print(f"silence: git diff {base} не выполнился — {d.stderr.strip()}", flush=True)
        return None
    out, path, line = {}, None, 0
    for row in d.stdout.splitlines():
        if row.startswith("+++ "):
            path = row[6:] if row.startswith("+++ b/") else None
            continue
        m = re.match(r"^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@", row)
        if m:
            line = int(m.group(1))
            continue
        if path and row.startswith("+"):
            out.setdefault(path, set()).add(line)
            line += 1
    for f in git(project, "ls-files", "--others", "--exclude-standard").stdout.splitlines():
        p = project / f
        if p.is_file():
            try:
                n = len(p.read_text(encoding="utf-8").splitlines())
            except UnicodeDecodeError:
                continue
            out[f] = set(range(1, n + 1))
    return out


def kind(path: str):
    name = Path(path).name
    if name.endswith(".go"):
        return "go"
    if name.endswith(".php"):
        return "php"
    if name in (".golangci.yml", ".golangci.yaml"):
        return "golangci"
    if re.match(r"^phpstan[\w.-]*\.neon(\.dist)?$", name):
        return "neon"
    return None


def comment_text(line: str, lang: str) -> str:
    """Текст комментария в строке (без маркеров комментария), пусто — комментария нет."""
    s = line.strip()
    if lang in ("golangci", "neon"):
        m = re.search(r"(?:^|\s)#\s?(.*)$", line)
        return m.group(1) if m else ""
    for start in ("//", "/*", "*", "#"):
        if s.startswith(start) and not s.startswith("#["):
            return s.lstrip("/*#").rstrip("*/ ")
    m = re.search(r"(?://|/\*|(?<!\S)#(?!\[))\s*(.*?)(?:\*/)?\s*$", s)
    return m.group(1) if m else ""


def has_reason(text: str) -> bool:
    """Причина — текст с хотя бы одним словом, кроме самих маркеров глушения."""
    text = PHPSTAN_MARKER.sub(" ", PHP_COVERAGE.sub(" ", NOLINT.sub(" ", text)))
    return bool(WORD.search(text))


def prev_nonblank(lines, i):
    j = i - 1
    while j >= 0 and not lines[j].strip():
        j -= 1
    return j


def prev_comment_reason(lines, i, lang) -> bool:
    j = prev_nonblank(lines, i)
    if j < 0:
        return False
    s = lines[j].strip()
    is_comment = s.startswith("#") if lang in ("golangci", "neon") else (
        s.startswith(("//", "/*", "*")) or (s.startswith("#") and not s.startswith("#[")))
    if not is_comment or NOLINT.search(s) or GO_SKIP.search(s) or PHP_SKIP.search(s):
        return False
    if lang == "php" and DOC_TAG.match(s):
        return False
    return has_reason(comment_text(lines[j], lang))


def explained(lines, i, lang) -> bool:
    return has_reason(comment_text(lines[i], lang)) or prev_comment_reason(lines, i, lang)


def skip_message(lines, i) -> bool:
    """Сообщение пропуска теста со словом — причина (вызов может переноситься на следующие строки)."""
    m = SKIP_MESSAGE.search("\n".join(lines[i:i + 3]))
    return bool(m and m.start() < len(lines[i]) and WORD.search(m.group(2)))


def code_findings(lines, added, lang, path=""):
    out = []
    go_test = path.endswith("_test.go")  # `x.Skip(n)` вне тестов — построители запросов, итераторы
    for n in sorted(added):
        i = n - 1
        if i >= len(lines):
            continue
        line = lines[i]
        if lang == "go":
            if NOLINT.search(line):
                m = NOLINT_OK.search(line)
                if not (m and WORD.search(m.group(1))):
                    out.append((n, "`//nolint` без линтера или причины — нужен `//nolint:<линтер> // причина`"))
            elif go_test and GO_SKIP.search(line) and not (skip_message(lines, i) or explained(lines, i, lang)):
                out.append((n, "пропуск теста без причины — нужно сообщение `t.Skip(\"…\")` или комментарий"))
            continue
        if PHPSTAN_IGNORE.search(line):
            m = PHPSTAN_REASON.search(line)
            if not (m and WORD.search(m.group(1))) and not explained(lines, i, lang):
                out.append((n, "`@phpstan-ignore` без причины (`(…)` после идентификатора или комментарий)"))
        elif PHP_SKIP.search(line) and not (skip_message(lines, i) or explained(lines, i, lang)):
            out.append((n, "пропуск теста без причины — нужно сообщение `markTestSkipped('…')` или комментарий"))
        elif PHP_COVERAGE.search(line) and not explained(lines, i, lang):
            out.append((n, "исключение из покрытия без причины"))
    return out


def indent(line):
    return len(line) - len(line.lstrip(" \t"))


def is_content(line):
    s = line.strip()
    return bool(s) and not s.startswith("#")


def parents(lines, i):
    """Индексы родителей строки по отступу (ближайший первым)."""
    out, cur = [], indent(lines[i])
    for j in range(i - 1, -1, -1):
        if is_content(lines[j]) and indent(lines[j]) < cur:
            out.append(j)
            cur = indent(lines[j])
            if cur == 0:
                break
    return out


def neon_level(text: str):
    m = re.search(r"^\s+level:\s*(\w+)", text, re.M)
    if not m:
        return None
    v = m.group(1)
    return 99 if v == "max" else int(v) if v.isdigit() else None


def config_findings(lines, added, lang, old_text):
    keys = GOLANGCI_KEYS if lang == "golangci" else NEON_KEYS
    out, items = [], set()
    for n in sorted(added):
        i = n - 1
        if i >= len(lines) or not is_content(lines[i]):
            continue
        line = lines[i]
        km = KEY.match(line)
        key = km.group(1) if km else None
        value = line.split(":", 1)[1].split("#")[0].strip() if km else ""
        what = None
        if lang == "neon" and key == "level" and old_text is not None:
            old, new = neon_level(old_text), neon_level("\n".join(lines))
            if old is not None and new is not None and new < old:
                what = f"уровень phpstan понижен: {old} → {new}"
        elif lang == "golangci" and key in GOLANGCI_FLAGS and value.lower() == "true":
            what = f"`{key}: true`"
        if what is None:
            chain = [i] + parents(lines, i)
            block = next((k for k, j in enumerate(chain) if (KEY.match(lines[j]) or [None, None])[1] in keys), None)
            if block is None:
                continue
            if km and not value:  # ключ без значения (сам блок, `rules:`) — записи ниже
                continue
            # запись блока целиком (внешний элемент списка под блоком) — одна находка
            item = ([j for j in chain[:block] if lines[j].lstrip().startswith("-")] or [i])[-1]
            if item in items:
                continue
            items.add(item)
            name = KEY.match(lines[chain[block]]).group(1)
            what = f"новая запись в `{name}`"
            # причина у самой строки или у её родителей до ключа блока включительно, если они тоже новые
            anchors = [j for j in chain[:block + 1] if j == i or (j + 1) in added]
            if any(explained(lines, j, lang) for j in anchors):
                continue
            out.append((n, what + " без причины комментарием"))
            continue
        if not explained(lines, i, lang):
            out.append((n, what + " без причины комментарием"))
    return out


def in_scope(path: str, sub) -> bool:
    """С `--sub` — файлы подпроекта и конфиги phpstan в корне проекта или подпроекта (остальной монорепозиторий — не задача)."""
    if not sub:
        return True
    sub = sub.strip("/").removeprefix("./")
    if path.startswith(sub + "/"):
        return True
    return kind(path) == "neon" and str(Path(path).parent) in (".", sub)


def findings(project: Path, base: str, sub=None):
    added = added_lines(project, base)
    if added is None:
        return None, 0
    added = {k: v for k, v in added.items() if in_scope(k, sub)}
    res, files = [], 0
    for path in sorted(added):
        lang = kind(path)
        p = project / path
        if not lang or not p.is_file():
            continue
        files += 1
        lines = p.read_text(encoding="utf-8").splitlines()
        if lang in ("go", "php"):
            found = code_findings(lines, added[path], lang, path)
        else:
            old = git(project, "show", f"{base}:./{path}")
            found = config_findings(lines, added[path], lang, old.stdout if old.returncode == 0 else None)
        res += [(path, n, msg) for n, msg in found]
    return res, files


def check(project: Path, base: str, sub=None) -> int:
    """Прогон для lint.py: находки в формате `файл:строка: [тег] …` и итоговая строка `silence: …`."""
    res, files = findings(project, base, sub)
    if res is None:
        return 2
    for path, n, msg in res:
        print(f"{path}:{n}: [{TAG}] {msg}", flush=True)
    print(f"silence: файлов: {files}; находок: {len(res)}", flush=True)
    return 1 if res else 0


def main(argv=None):
    p = argparse.ArgumentParser(description="Глушение проверок в диффе — только добавленные строки")
    p.add_argument("project")
    p.add_argument("--base", required=True, help="ревизия, от которой смотреть новое (дифф задачи)")
    p.add_argument("--sub", help="подпроект монорепозитория: только его файлы и конфиги phpstan в корне проекта и подпроекта")
    a = p.parse_args(argv)
    return check(Path(a.project).expanduser().resolve(), a.base, a.sub)


if __name__ == "__main__":
    sys.exit(main())
