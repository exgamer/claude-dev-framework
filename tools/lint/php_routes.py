#!/usr/bin/env python3
"""Маршруты PHP-подпроекта (`Entrypoints/**/routes.php`) по `stacks/php/conventions.md` п. 18, 20 — без PHP, разбором текста.

    python3 tools/lint/php_routes.py <каталог подпроекта> <корневой namespace> [--base REV]

Правила (имя — в `tools/check/lint-proof/RULES.md`, колонка «Правило конфига»):
- `routes:api-response-first` — группа или маршрут верхнего уровня routes.php: первый middleware — middleware ответа
  (тег `api-response-not-first`): `api.response`, алиас `ApiResponseMiddleware` из `bootstrap/app.php` проекта, сам
  `ApiResponseMiddleware::class` или группа `$middleware->group(…)`, в которой он есть; группа, где он не первым, — одна
  находка в `bootstrap/app.php`; middleware ответа нет ни в routes.php подпроекта, ни в `bootstrap/app.php` — правило
  не проверяется, об этом строка в выводе;
- `routes:middleware-alias` — middleware маршрута `X::class`, у которого есть алиас в `bootstrap/app.php`
  (тег `route-middleware-not-aliased`);
- `routes:no-id-constraint` — ограничение параметра-id в пути (`whereNumber`/`where`/`whereIn`/`whereAlphaNumeric`/
  `whereUuid`/`whereUlid` по `id`, `*_id`, `*Id`; `Route::pattern(s)`) — id ≤ 0 и не число дают 404 вместо 422 (тег `route-id-constrained`);
- `routes:permission-403` — маршрут под `permission:…` (свой или группы) на методе контроллера `[X::class, 'метод']`:
  в OpenAPI-атрибутах метода есть ответ 403 (тег `swagger-status-mismatch`).
С `--base` — только находки, задетые добавленными строками диффа (`git diff -U0 REV`, новые файлы — целиком): строка
ограничения id, цепочка группы/маршрута для `api.response`, цепочка маршрута, объемлющих групп или атрибуты метода
контроллера для 403; удалённые строки (убранный `'api.response'`, удалённый атрибут 403) — не видны;
старые строки изменённого routes.php — не находка задачи. Код возврата: 0 — чисто, 1 — находки, 2 — git не выполнился.
"""
import re
import sys
from pathlib import Path

API = "api.response"
API_CLASS = "ApiResponseMiddleware"
# элемент списка middleware: `X::class` (с `. ':параметр'` или без) или строка
TOKEN = re.compile(r"""\\?([A-Za-z_][\w\\]*)::class(?:\s*\.\s*(['"])(?:\\.|(?!\2).)*\2)?|(['"])((?:\\.|(?!\3).)*)\3""")
VERBS = r"get|post|put|patch|delete|options|any|match"
ID_PARAM = re.compile(r"^(id|\w+_id|\w+Id)$")
CONSTRAINTS = r"whereNumber|whereAlphaNumeric|whereAlpha|whereUuid|whereUlid|whereIn|where"
FORBIDDEN_403 = re.compile(r"response:\s*(?:['\"]?403['\"]?|[\w\\]*::HTTP_FORBIDDEN)\b")


class Source:
    """Текст PHP без комментариев (позиции и строки сохранены), глубина скобок и признак «внутри строки» по символам."""

    def __init__(self, text: str):
        out, instr = list(text), [False] * len(text)
        i, n = 0, len(text)
        while i < n:
            c = text[i]
            if c in "'\"":
                j = i + 1
                while j < n and text[j] != c:
                    j += 2 if text[j] == "\\" else 1
                for k in range(i, min(j + 1, n)):
                    instr[k] = True
                i = j + 1
            elif text.startswith("//", i) or c == "#" and not text.startswith("#[", i):
                j = text.find("\n", i)
                j = n if j < 0 else j
                out[i:j] = " " * (j - i)
                i = j
            elif text.startswith("/*", i):
                j = text.find("*/", i + 2)
                j = n if j < 0 else j + 2
                out[i:j] = [ch if ch == "\n" else " " for ch in text[i:j]]
                i = j
            else:
                i += 1
        self.code, self.instr = "".join(out), instr
        self.depth, d = [0] * n, 0
        for k, ch in enumerate(self.code):
            if not instr[k] and ch in ")]}":
                d -= 1
            self.depth[k] = d
            if not instr[k] and ch in "([{":
                d += 1

    def line(self, pos: int) -> int:
        return self.code.count("\n", 0, pos) + 1

    def close(self, open_pos: int) -> int:
        """Позиция парной закрывающей скобки."""
        d = self.depth[open_pos]
        for k in range(open_pos + 1, len(self.code)):
            if not self.instr[k] and self.code[k] in ")]}" and self.depth[k] == d:
                return k
        return len(self.code)

    def code_matches(self, pattern: str, start: int = 0, end: int | None = None):
        for m in re.finditer(pattern, self.code[start:end] if end is not None else self.code[start:]):
            if not self.instr[start + m.start()]:
                yield start + m.start(), m

    def strings(self, start: int, end: int) -> list[str]:
        return [m.group(2) for m in re.finditer(r"(['\"])((?:\\.|(?!\1).)*)\1", self.code[start:end])]


def statements(src: Source) -> list[dict]:
    """Цепочки `Route::…;`: начало, конец, глубина, префикс до `->group(` и тело группы."""
    out = []
    for pos, _ in src.code_matches(r"\bRoute::"):
        d = src.depth[pos]
        end = next((k for k in range(pos, len(src.code)) if src.code[k] == ";" and not src.instr[k] and src.depth[k] == d), len(src.code))
        group = next((p for p, _ in src.code_matches(r"->\s*group\s*\(", pos, end) if src.depth[p] == d), None)
        body = None
        if group is not None:
            op = src.code.index("(", group)
            body = (op, src.close(op))
        out.append({"pos": pos, "end": end, "depth": d, "chain_end": group if group is not None else end, "body": body,
                    "route": re.match(rf"Route::\s*({VERBS})\s*\(", src.code[pos:]) is not None})
    return out


def tokens(src: Source, start: int, end: int, imports: dict[str, str]) -> list[tuple[str, str | None, int]]:
    """Элементы списка middleware по порядку: (как написан, полное имя класса для `X::class` или None, позиция)."""
    out = []
    for m in TOKEN.finditer(src.code[start:end]):
        if m.group(1):
            out.append((m.group(1) + "::class", resolve(m.group(1), imports), start + m.start()))
        else:
            out.append((m.group(4), None, start + m.start()))
    return out


def resolve(name: str, imports: dict[str, str]) -> str:
    head, _, rest = name.partition("\\")
    return imports[head] + ("\\" + rest if rest else "") if head in imports else name


def middleware_tokens(src: Source, st: dict, imports: dict[str, str]) -> list[tuple[str, str | None, int]]:
    """Middleware цепочки в порядке объявления (без вложенных групп)."""
    out = []
    for p, m in src.code_matches(r"(?:Route::|->)\s*middleware\s*\(", st["pos"], st["chain_end"]):
        if src.depth[p] != st["depth"]:
            continue
        op = p + len(m.group(0)) - 1
        out += tokens(src, op, src.close(op), imports)
    return out


def middlewares(src: Source, st: dict) -> list[str]:
    """Строковые middleware цепочки в порядке объявления (без вложенных групп)."""
    return [t for t, cls, _ in middleware_tokens(src, st, {}) if cls is None]


class App:
    """Регистрация middleware в `bootstrap/app.php` проекта: алиасы (`$middleware->alias`) и группы (`group`,
    `appendToGroup`, `prependToGroup`). Файл ищется от каталога подпроекта вверх до корня проекта (`composer.json`)."""

    def __init__(self, sub: Path):
        self.path, self.aliases, self.groups, self.lines = None, {}, {}, {}
        for d in [sub, *sub.parents]:
            if (d / "bootstrap" / "app.php").is_file():
                self.path = d / "bootstrap" / "app.php"
                break
            if (d / "composer.json").is_file() or (d / ".git").exists():
                break
        if not self.path:
            return
        text = self.path.read_text(encoding="utf-8")
        src, imports = Source(text), uses(text)
        for p, m in src.code_matches(r"->\s*alias\s*\(\s*\["):
            op = p + len(m.group(0)) - 1
            for a in re.finditer(r"""(['"])([^'"]+)\1\s*=>\s*\\?([A-Za-z_][\w\\]*)::class""", src.code[op:src.close(op)]):
                self.aliases[a.group(2)] = resolve(a.group(3), imports)
        for p, m in src.code_matches(r"""->\s*(group|appendToGroup|prependToGroup)\s*\(\s*(['"])([\w.:-]+)\2\s*,"""):
            op = src.code.index("(", p)
            items = tokens(src, p + len(m.group(0)), src.close(op), imports)
            name, kind = m.group(3), m.group(1)
            cur = self.groups.get(name, [None] if kind != "group" else [])  # None — состав группы Laravel по умолчанию не известен
            self.groups[name] = items + cur if kind == "prependToGroup" else (cur + items if kind == "appendToGroup" else items)
            self.lines.setdefault(name, src.line(p))

    def is_api(self, token: str, cls: str | None, seen=()) -> bool:
        """Элемент — middleware ответа: `api.response`, его алиас, сам класс или группа, где он первым."""
        if cls is not None:
            return cls.rsplit("\\", 1)[-1] == API_CLASS
        if token == API or (token in self.aliases and self.aliases[token].rsplit("\\", 1)[-1] == API_CLASS):
            return True
        group = self.groups.get(token)
        return bool(group) and group[0] is not None and token not in seen and self.is_api(group[0][0], group[0][1], seen + (token,))

    def api_inside(self, token: str) -> bool:
        """Группа, в которой middleware ответа есть, но не первым."""
        return any(x is not None and self.is_api(x[0], x[1]) for x in self.groups.get(token, [])[1:])

    def alias_of(self, cls: str) -> str | None:
        return next((a for a, c in sorted(self.aliases.items()) if c == cls), None)


def uses(text: str) -> dict[str, str]:
    out = {}
    for full, alias in re.findall(r"^use\s+([\w\\]+)(?:\s+as\s+(\w+))?\s*;", text, re.M):
        out[alias or full.rsplit("\\", 1)[-1]] = full
    return out


def check_403(sub: Path, root: str, routes: Path, src: Source, st: dict, own: list[str], encl: list[dict], imports):
    groups = [middlewares(src, g) for g in encl]
    if not any(m.startswith("permission:") for m in own + [g for mw in groups for g in mw]):
        return None
    act = re.search(r"\[\s*\\?([\w\\]+)::class\s*,\s*['\"](\w+)['\"]\s*\]", src.code[st["pos"]:st["end"]])
    if not act:
        return None
    name, method = act.groups()
    fqcn = resolve(name, imports)
    if not fqcn.startswith(root + "\\"):
        return None
    ctrl = sub / (fqcn[len(root) + 1:].replace("\\", "/") + ".php")
    if not ctrl.is_file():
        return None
    text = ctrl.read_text(encoding="utf-8")
    fn = re.search(rf"\bfunction\s+{method}\s*\(", text)
    if not fn:
        return None
    prev = max(text.rfind("\n    }\n", 0, fn.start()), text.rfind("\n{\n", 0, fn.start()), 0)
    if FORBIDDEN_403.search(text[prev:fn.start()]):
        return None
    line = text.count("\n", 0, fn.start()) + 1
    # цепочки объемлющих групп — тоже: `permission:`, добавленный в middleware существующей группы, задевает её маршруты
    spans = [(routes, src.line(st["pos"]), src.line(st["end"])), (ctrl, text.count("\n", 0, prev) + 1, line)] \
        + [(routes, src.line(g["pos"]), src.line(g["chain_end"])) for g in encl]
    return (ctrl, line, f"маршрут под {next(m for m in own + [g for mw in groups for g in mw] if m.startswith('permission:'))} "
            f"({routes.name}:{src.line(st['pos'])}) — в OpenAPI-атрибутах {ctrl.stem}::{method}() нет ответа 403 "
            "(swagger-status-mismatch)", spans)


def check(sub: Path, root: str) -> tuple[list[tuple], list[str]]:
    """Находки `(файл, строка, сообщение, [(файл, первая строка, последняя строка), …])` — последнее: строки, правка
    которых задевает находку (для `--base`), и заметки."""
    findings, notes = [], []
    files = sorted(sub.glob("Entrypoints/**/routes.php"))
    app = App(sub)
    texts = {f: f.read_text(encoding="utf-8") for f in files}
    api_known = any(re.search(rf"['\"]{re.escape(API)}['\"]|\b{API_CLASS}::class", x) for x in texts.values()) \
        or any(app.is_api(a, None) for a in app.aliases)
    if files and not api_known:
        notes.append(f"middleware ответа ('{API}', алиас {API_CLASS} в bootstrap/app.php, {API_CLASS}::class) нет ни в одном "
                     "routes.php и bootstrap/app.php — routes:api-response-first не проверялось")
    inside = set()  # группы bootstrap/app.php, где middleware ответа не первым и которые стоят первыми в маршрутах
    for f in files:
        text = texts[f]
        src, imports = Source(text), uses(text)
        sts = statements(src)
        for st in sts:
            toks = middleware_tokens(src, st, imports)
            own = [t for t, cls, _ in toks if cls is None]
            for tok, cls, p in toks:
                alias = app.alias_of(cls) if cls else None
                if alias:
                    findings.append((f, src.line(p), f"{tok} в middleware — у класса есть алиас '{alias}' ({app.path.parent.name}/app.php): "
                                     "подключать алиасом (route-middleware-not-aliased)", [(f, src.line(p), src.line(p))]))
            if api_known and st["depth"] == 0 and (st["route"] or st["body"]):
                if toks and app.api_inside(toks[0][0]) and not app.is_api(toks[0][0], toks[0][1]):
                    inside.add(toks[0][0])
                elif not toks or not app.is_api(toks[0][0], toks[0][1]):
                    first = f"первый — '{toks[0][0]}'" if toks else "middleware нет"
                    where = "у группы маршрутов" if st["body"] else "у маршрута вне группы"
                    findings.append((f, src.line(st["pos"]), f"middleware ответа ({API} или алиас {API_CLASS}) не первым {where}: {first} (api-response-not-first)",
                                     [(f, src.line(st["pos"]), src.line(st["chain_end"]))]))
            for p, m in src.code_matches(rf"(?:->\s*(?:{CONSTRAINTS})|Route::\s*patterns?)\s*\(", st["pos"], st["chain_end"]):
                if src.depth[p] != st["depth"]:
                    continue
                op = p + len(m.group(0)) - 1
                ids = [s for s in src.strings(op, src.close(op)) if ID_PARAM.match(s)]
                if ids:
                    call = re.sub(r"[\s>(-]|Route::", "", m.group(0))
                    findings.append((f, src.line(p), f"{call}('{ids[0]}') — ограничение id в пути: id ≤ 0 или не число даст 404 вместо 422; "
                                     "проверка — в Request (route-id-constrained)", [(f, src.line(p), src.line(src.close(op)))]))
            if st["route"]:
                encl = [g for g in sts if g["body"] and g["body"][0] < st["pos"] < g["body"][1]]
                hit = check_403(sub, root, f, src, st, own, encl, imports)
                if hit:
                    findings.append(hit)
    for g in sorted(inside):
        first = app.groups[g][0]
        findings.append((app.path, app.lines[g], f"middleware ответа ({API_CLASS}) не первым в группе middleware '{g}': первый — "
                         f"'{first[0] if first else 'состав группы Laravel по умолчанию'}' (api-response-not-first)",
                         [(app.path, app.lines[g], app.lines[g])]))
    return findings, notes


def touched(findings: list[tuple], sub: Path, base: str):
    """Находки, задетые добавленными строками диффа с `base` (None — git не выполнился)."""
    import diff_silence
    added = diff_silence.added_lines(sub, base)  # пути от каталога подпроекта
    if added is None:
        return None
    def hit(path: Path, first: int, last: int) -> bool:
        try:
            rel = path.resolve().relative_to(sub).as_posix()
        except ValueError:
            return False
        return any(first <= n <= last for n in added.get(rel, ()))
    return [x for x in findings if any(hit(*span) for span in x[3])]


def main(argv: list[str]) -> int:
    base = None
    if "--base" in argv:
        k = argv.index("--base")
        base, argv = argv[k + 1], argv[:k] + argv[k + 2:]
    if len(argv) < 2:
        sys.exit(__doc__)
    sub, root = Path(argv[0]).resolve(), argv[1].strip("\\")
    findings, notes = check(sub, root)
    if base:
        findings = touched(findings, sub, base)
        if findings is None:
            return 2
    for f, line, msg, _ in findings:
        print(f"{f}:{line}: {msg}")
    for n in notes:
        print(f"routes: {n}")
    print(f"routes: файлов маршрутов: {len(list(sub.glob('Entrypoints/**/routes.php')))}; находок: {len(findings)}")
    return 1 if findings else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
