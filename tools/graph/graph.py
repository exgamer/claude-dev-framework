#!/usr/bin/env python3
"""Граф правил фреймворка (graphify): корпус, чанки для агентов, сборка графа и отчёта.

Запуск из корня репозитория интерпретатором, где установлен graphify:
  PY=$(head -1 "$(which graphify)" | tr -d '#!')
  $PY tools/graph/graph.py sync      # корпус правил → tools/graph/corpus/ (не коммитится)
  $PY tools/graph/graph.py prepare   # что не в кэше → чанки .chunk_N.txt для агентов
  $PY tools/graph/graph.py finish    # чанки агентов + кэш → graph.json, graph.html, GRAPH_REPORT.md, stats.md
"""
import hashlib
import json
import shutil
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
ROOT = HERE.parent.parent
OUT = HERE / "graphify-out"
CORPUS_DIRS = ["core", "approaches", "regulations", "architecture", "stacks", "skills", "agents"]
SKIP_PARTS = {"official", "reference"}
SKIP_NAMES = {"decisions-log.md"}
CHUNK_SIZE = 22
FULL_HASHES = OUT / "full-hashes.json"


def rel(p: Path) -> str:
    return str(p.relative_to(HERE))


def sync() -> None:
    corpus = HERE / "corpus"
    shutil.rmtree(corpus, ignore_errors=True)
    n = 0
    for d in CORPUS_DIRS:
        for f in sorted((ROOT / d).rglob("*.md")):
            r = f.relative_to(ROOT)
            if SKIP_PARTS & set(r.parts) or f.name in SKIP_NAMES:
                continue
            dst = corpus / r
            dst.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(f, dst)
            n += 1
    print(f"corpus: {n} files")


def full_hash(f: str) -> str:
    return hashlib.sha256(Path(f).read_bytes()).hexdigest()


def prepare() -> None:
    from graphify.cache import check_semantic_cache, file_hash
    from graphify.detect import detect

    det = detect(Path("corpus"))
    files = sorted(f for v in det["files"].values() for f in v)
    # graphify не хэширует YAML-frontmatter .md — правка только description скилла/агента не сбросила бы кэш
    known = json.loads(FULL_HASHES.read_text()) if FULL_HASHES.exists() else {}
    for f in files:
        if f in known and known[f] != full_hash(f):
            (OUT / "cache" / f"{file_hash(Path(f))}.json").unlink(missing_ok=True)
    _, _, _, uncached = check_semantic_cache(files)
    uncached = sorted(uncached)
    for old in list(OUT.glob(".chunk_*.txt")) + list(OUT.glob(".graphify_chunk_*.json")):
        old.unlink()
    chunks = [uncached[i:i + CHUNK_SIZE] for i in range(0, len(uncached), CHUNK_SIZE)]
    for i, ch in enumerate(chunks, 1):
        (OUT / f".chunk_{i}.txt").write_text("\n".join(str((HERE / f).resolve()) for f in ch))
    print(f"files: {len(files)}, cached: {len(files) - len(uncached)}, to extract: {len(uncached)} → {len(chunks)} chunk(s)")
    for i in range(1, len(chunks) + 1):
        print(f"  {OUT / f'.chunk_{i}.txt'} → {OUT / f'.graphify_chunk_{i:02d}.json'}")


def finish() -> None:
    import networkx as nx
    from graphify.analyze import god_nodes, suggest_questions, surprising_connections
    from graphify.build import build_from_json
    from graphify.cache import check_semantic_cache, save_semantic_cache
    from graphify.cluster import cluster, score_all
    from graphify.detect import detect
    from graphify.export import to_html, to_json
    from graphify.report import generate

    det = detect(Path("corpus"))
    files = sorted(f for v in det["files"].values() for f in v)

    new_n, new_e, new_h = [], [], []
    for p in sorted(OUT.glob(".graphify_chunk_*.json")):
        d = json.loads(p.read_text())
        new_n += d["nodes"]
        new_e += d["edges"]
        new_h += d.get("hyperedges", [])
    if new_n:
        save_semantic_cache(new_n, new_e, new_h)
    nodes, edges, hyper, missing = check_semantic_cache(files)
    if missing:
        print(f"WARNING: no extraction for {len(missing)} file(s): {missing[:5]}")

    seen, uniq = set(), []
    for n in nodes:
        if n["id"] not in seen:
            seen.add(n["id"])
            uniq.append(n)
    # тема объявлена узлом только в одном файле чанка — после переразбора того файла её узел мог пропасть
    for e in edges:
        for end in (e["source"], e["target"]):
            if end.startswith("topic_") and end not in seen:
                seen.add(end)
                uniq.append({"id": end, "label": end[len("topic_"):], "file_type": "document", "source_file": e.get("source_file")})
    dropped = [e for e in edges if e["source"] not in seen or e["target"] not in seen]
    edges = [e for e in edges if e["source"] in seen and e["target"] in seen]
    if dropped:
        print(f"dropped {len(dropped)} edge(s) to unknown nodes (cites вне корпуса и т. п.)")
    ex = {"nodes": uniq, "edges": edges, "hyperedges": hyper, "input_tokens": 0, "output_tokens": 0}

    G = build_from_json(ex)
    C = cluster(G)
    co = score_all(G, C)
    lab_path = OUT / "labels.json"
    old = json.loads(lab_path.read_text()) if lab_path.exists() else {}
    labels = {c: old.get(str(c), f"Community {c}") for c in C}
    gods = god_nodes(G)
    sur = surprising_connections(G, C)
    q = suggest_questions(G, C, labels)
    (OUT / "GRAPH_REPORT.md").write_text(generate(G, C, co, labels, gods, sur, det, {"input": 0, "output": 0}, "dev-framework rules", suggested_questions=q))
    to_json(G, C, str(OUT / "graph.json"))
    to_html(G, C, str(OUT / "graph.html"), community_labels=labels)
    lab_path.write_text(json.dumps({str(k): v for k, v in labels.items()}, ensure_ascii=False, indent=2))

    label = lambda n: G.nodes[n].get("label", n)
    D = nx.DiGraph()
    D.add_edges_from((e["source"], e["target"]) for e in edges if e["relation"] == "cites")
    file_nodes = [n for n in G if n.startswith("file_")]
    inc = {f: (D.in_degree(f) if f in D else 0) for f in file_nodes}
    topics = {}
    for e in edges:
        if e["relation"] == "references" and e["target"].startswith("topic_"):
            topics.setdefault(e["target"], set()).add(e["source"])
    conflicts = [e for e in edges if e["relation"] == "conflicts_with"]
    nmap = {n["id"]: n for n in uniq}
    lines = [
        "# Граф правил — сводка",
        "",
        f"Узлов {G.number_of_nodes()}, связей {G.number_of_edges()}, групп {len(C)}. Кандидаты ниже — подсказки, проверять по файлам.",
        "",
        "## Сироты (на файл нет ссылок)",
        *[f"- {label(f)}" for f in file_nodes if inc[f] == 0],
        "",
        "## Темы в большем числе файлов",
        *[f"- {label(t)} — {len(s)}" for t, s in sorted(topics.items(), key=lambda x: -len(x[1]))[:20]],
        "",
        "## Кандидаты в противоречия (conflicts_with)",
    ]
    for i, e in enumerate(conflicts, 1):
        a, b = nmap[e["source"]], nmap[e["target"]]
        lines.append(f"C{i}. [{a.get('source_file', '').replace('corpus/', '')} § {a.get('source_location')}] «{a['label']}» VS "
                     f"[{b.get('source_file', '').replace('corpus/', '')} § {b.get('source_location')}] «{b['label']}» ({e['confidence_score']})")
    (OUT / "stats.md").write_text("\n".join(lines) + "\n")

    FULL_HASHES.write_text(json.dumps({f: full_hash(f) for f in files}, indent=2))
    for p in list(OUT.glob(".chunk_*.txt")) + list(OUT.glob(".graphify_chunk_*.json")):
        p.unlink()
    print(f"graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges, {len(C)} communities, {len(conflicts)} conflict candidates")
    print(f"  {OUT / 'stats.md'}\n  {OUT / 'graph.html'}")


if __name__ == "__main__":
    import os

    os.chdir(HERE)
    OUT.mkdir(exist_ok=True)
    {"sync": sync, "prepare": prepare, "finish": finish}[sys.argv[1]]()
