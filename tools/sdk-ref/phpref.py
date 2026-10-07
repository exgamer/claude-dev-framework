#!/usr/bin/env python3
"""phpref — справочник по PHP-пакету из исходников: классы/интерфейсы/трейты/enum,
public-методы с сигнатурой и первой строкой docblock, константы и кейсы enum.

    python3 -I phpref.py <package-dir> [<package-dir>...] > reference.md

package-dir — каталог пакета с composer.json (например vendor/mps/core).
"""
import json
import os
import re
import sys

DECL = re.compile(r'^\s*(?:(?:final|abstract|readonly)\s+)*(class|interface|trait|enum)\s+(\w+)([^{]*)\{', re.M)
METHOD = re.compile(r'^\s*((?:(?:public|static|final|abstract)\s+)*)function\s+&?(\w+)\s*\(', re.M)
CONST = re.compile(r'^\s*(?:final\s+)?(?:public\s+)?const\s+(?:\w+\s+)?(\w+)\s*=', re.M)
CASE = re.compile(r'^\s*case\s+(\w+)', re.M)
NS = re.compile(r'^\s*namespace\s+([\w\\]+)\s*;', re.M)


def summary(doc):
    if not doc:
        return ''
    lines = []
    for raw in doc.strip('/*').splitlines():
        line = raw.strip().lstrip('*').strip()
        if line.startswith('@'):
            break
        if not line and lines:
            break
        if line:
            lines.append(line)
    text = ' '.join(lines)
    return text[:220] + ('…' if len(text) > 220 else '')


def docblock_before(src, pos):
    head = src[:pos].rstrip()
    while True:
        # пропустить атрибуты #[...] между docblock и объявлением
        if head.endswith(']'):
            i = head.rfind('#[')
            if i < 0:
                break
            head = head[:i].rstrip()
            continue
        break
    if head.endswith('*/'):
        start = head.rfind('/**')
        if start >= 0:
            return head[start:]
    return ''


def signature(src, start):
    depth, i = 0, src.index('(', start)
    j = i
    while j < len(src):
        c = src[j]
        if c == '(':
            depth += 1
        elif c == ')':
            depth -= 1
            if depth == 0:
                break
        j += 1
    params = ' '.join(src[i + 1:j].split())
    params = re.sub(r'#\[[^\]]*\]\s*', '', params)
    params = re.sub(r',\s*$', '', params)
    rest = src[j + 1:j + 200]
    m = re.match(r'\s*:\s*([^{;]+)', rest)
    ret = ' '.join(re.sub(r'//.*', '', m.group(1)).split()) if m else ''
    return params, ret


def class_body(src, open_brace):
    depth = 0
    for j in range(open_brace, len(src)):
        if src[j] == '{':
            depth += 1
        elif src[j] == '}':
            depth -= 1
            if depth == 0:
                return src[open_brace + 1:j]
    return src[open_brace + 1:]


def top_level(body, pattern):
    """Совпадения pattern только на верхнем уровне тела класса (не внутри методов)."""
    depth, out, last = 0, [], 0
    marks = []
    for j, c in enumerate(body):
        if c == '{':
            depth += 1
        elif c == '}':
            depth -= 1
        marks.append(depth)
    for m in pattern.finditer(body):
        if marks[m.start()] == 0 if m.start() < len(marks) else True:
            out.append(m)
    return out


def file_entries(path):
    src = open(path, encoding='utf-8', errors='replace').read()
    ns = NS.search(src)
    ns = ns.group(1) if ns else ''
    out = []
    for d in DECL.finditer(src):
        kind, name, tail = d.group(1), d.group(2), ' '.join(d.group(3).split())
        body = class_body(src, d.end() - 1)
        entry = {
            'kind': kind,
            'fqcn': (ns + '\\' + name) if ns else name,
            'tail': tail,
            'doc': summary(docblock_before(src, d.start())),
            'consts': [m.group(1) for m in top_level(body, CONST)],
            'cases': [m.group(1) for m in top_level(body, CASE)] if kind == 'enum' else [],
            'methods': [],
        }
        for m in top_level(body, METHOD):
            mods = m.group(1)
            if kind == 'class' or kind == 'trait' or kind == 'enum':
                if 'public' not in mods and not (mods.strip() == '' and kind != 'trait'):
                    continue
                if mods.strip() == '' and kind == 'class':
                    pass  # без модификатора = public
            params, ret = signature(body, m.start())
            static = 'static ' if 'static' in mods else ''
            sig = f"{static}{m.group(2)}({params})" + (f": {ret}" if ret else '')
            entry['methods'].append((sig, summary(docblock_before(body, m.start()))))
        out.append(entry)
    return out


def package(root):
    meta = {}
    try:
        meta = json.load(open(os.path.join(root, 'composer.json')))
    except Exception:
        pass
    name = meta.get('name', os.path.basename(root))
    version = os.environ.get('PHPREF_VERSION') or meta.get('version', '')
    print(f'# {name} {version}'.rstrip() + '\n')
    src_root = os.path.join(root, 'src') if os.path.isdir(os.path.join(root, 'src')) else root
    groups = {}
    for dirpath, dirnames, filenames in os.walk(src_root):
        dirnames[:] = sorted(d for d in dirnames if d not in ('vendor', 'tests', 'Tests', '.git'))
        for f in sorted(filenames):
            if f.endswith('.php'):
                rel = os.path.relpath(dirpath, src_root)
                groups.setdefault(rel, []).extend(file_entries(os.path.join(dirpath, f)))
    for rel in sorted(groups):
        entries = groups[rel]
        if not entries:
            continue
        print(f"## {'src' if rel == '.' else 'src/' + rel}\n")
        for e in entries:
            line = f"- {e['kind']} `{e['fqcn']}`"
            if e['tail']:
                line += f" _{e['tail']}_"
            if e['doc']:
                line += f" — {e['doc']}"
            print(line)
            if e['cases']:
                print(f"  - кейсы: `{'`, `'.join(e['cases'][:15])}`" + (' …' if len(e['cases']) > 15 else ''))
            if e['consts']:
                print(f"  - константы: `{'`, `'.join(e['consts'][:15])}`" + (' …' if len(e['consts']) > 15 else ''))
            for sig, doc in e['methods']:
                print(f"  - `{sig}`" + (f" — {doc}" if doc else ''))
        print()


if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    for root in sys.argv[1:]:
        package(root)
