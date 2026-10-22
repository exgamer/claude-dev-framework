#!/usr/bin/env python3
"""Учёт агентов и токенов по задаче /dev — из транскриптов Claude Code.

Источник — ~/.claude/projects/<путь проекта>/<сессия>.jsonl (оркестратор) и
<сессия>/subagents/agent-*.jsonl + .meta.json (роли). Агент относится к задаче,
если в его поручении есть `docs/tasks/<ключ>` или «Задача: <ключ>».
Только читает транскрипты; пишет лишь `docs/tasks/<ключ>/usage.md` при --write.

Точность: вход, запись и чтение кэша — точные. Выход в транскрипте записан в начале
ответа и занижен, текст размышлений не сохраняется, поэтому выход — нижняя граница,
а рядом — объём видимого вывода (текст + аргументы инструментов) в символах.
<subagent_tokens> из уведомления о завершении агента — размер контекста в конце, не расход.

  usage.py [--project PATH]                 — список задач с итогами
  usage.py [--project PATH] --task KEY      — лог задачи в stdout
  usage.py [--project PATH] --task KEY --write
  usage.py [--project PATH] --session ID [--since ISO] [--match REGEX] [--out FILE]
                                            — агенты одной сессии без ключа задачи (проверки /dev-rule,
                                              полная сверка): по агентам, по этапам (описание до «:»), итог
  usage.py [--project PATH] --context [--session ID]
                                            — контекст последнего вызова сессии (по умолчанию — самой свежей):
                                              по нему оркестратор /dev решает о перезапуске сессии

«Стоимость» — в единицах цены входа модели: вход 1, запись кэша 1,25 (TTL 5 мин) / 2 (1 час), чтение кэша
0,05 (Opus 5.5) / 0,025 (Fable 5.1) / 0,1 (остальные), выход 5; разбивка записи по TTL — из `usage.cache_creation`.
«Перезапись» — запись кэша в вызовах после паузы дольше TTL (кэш остыл, контекст записан заново); у агентов
кэш живёт 5 минут — поэтому доводка закончившегося агента после паузы стоит как запись всего его контекста.
"""
import argparse
import json
import re
import sys
from collections import defaultdict
from datetime import datetime
from pathlib import Path

PROJECTS = Path.home() / ".claude" / "projects"
TASK_RE = re.compile(r"docs/tasks/([A-Za-z0-9._-]+)|Задача:\s*([A-Za-z0-9._-]+)")
PLAN_ID_RE = re.compile(r"\b(T\d+[a-z]?)\b")
FIELDS = ("input_tokens", "cache_creation_input_tokens", "cache_read_input_tokens", "output_tokens")
READ_PRICE = {"claude-opus-5-5": 0.05, "claude-fable-5-1": 0.025, "claude-mythos-5-1": 0.025}  # остальные — 0,1


def call_cost(u, model):
    """Стоимость вызова в единицах цены входа модели."""
    cc = u.get("cache_creation") or {}
    w5, w1h = cc.get("ephemeral_5m_input_tokens"), cc.get("ephemeral_1h_input_tokens")
    if w5 is None and w1h is None:
        w5, w1h = u.get("cache_creation_input_tokens") or 0, 0
    return ((u.get("input_tokens") or 0) + 1.25 * (w5 or 0) + 2 * (w1h or 0)
            + READ_PRICE.get(model, 0.1) * (u.get("cache_read_input_tokens") or 0) + 5 * (u.get("output_tokens") or 0))


def project_dir(project: Path) -> Path:
    return PROJECTS / re.sub(r"[^A-Za-z0-9]", "-", str(project.resolve()))


def ts(value):
    return datetime.fromisoformat(value.replace("Z", "+00:00")) if value else None


def read_transcript(path: Path):
    """Токены по уникальным ответам (одна запись API пишется несколькими строками), модель, время, первое поручение."""
    usage_by_msg, models, times, prompt, out_chars = {}, set(), [], "", 0
    call_meta = {}  # id ответа → (время первой строки, модель)
    with path.open(encoding="utf-8") as f:
        for line in f:
            try:
                d = json.loads(line)
            except json.JSONDecodeError:
                continue
            if d.get("timestamp"):
                times.append(d["timestamp"])
            msg = d.get("message") or {}
            if d.get("type") == "user" and not prompt:
                content = msg.get("content")
                if isinstance(content, str):
                    prompt = content
                elif isinstance(content, list):
                    prompt = " ".join(c.get("text", "") for c in content if isinstance(c, dict))
            if d.get("type") == "assistant" and msg.get("usage"):
                mid = msg.get("id") or d.get("uuid")
                usage_by_msg[mid] = msg["usage"]
                call_meta.setdefault(mid, (d.get("timestamp"), msg.get("model") or ""))
                for c in msg.get("content") or []:
                    if c.get("type") == "text":
                        out_chars += len(c.get("text", ""))
                    elif c.get("type") == "tool_use":
                        out_chars += len(json.dumps(c.get("input"), ensure_ascii=False))
                if msg.get("model") and not msg["model"].startswith("<"):
                    models.add(msg["model"])
    tokens = {k: sum(u.get(k) or 0 for u in usage_by_msg.values()) for k in FIELDS}
    cost, rewrite, prev, context = 0.0, 0, None, 0
    for mid, u in sorted(usage_by_msg.items(), key=lambda x: call_meta[x[0]][0] or ""):
        when, model = call_meta[mid]
        cost += call_cost(u, model)
        cc = u.get("cache_creation") or {}
        ttl = 3600 if (cc.get("ephemeral_1h_input_tokens") or 0) > (cc.get("ephemeral_5m_input_tokens") or 0) else 300
        written = u.get("cache_creation_input_tokens") or 0
        context = (u.get("input_tokens") or 0) + written + (u.get("cache_read_input_tokens") or 0)
        t_now = ts(when)
        if prev and t_now and (t_now - prev).total_seconds() > ttl and written * 2 > context:
            rewrite += written
        prev = t_now or prev
    return {
        "cost": cost,
        "rewrite": rewrite,
        "context": context,
        "tokens": tokens,
        "calls": len(usage_by_msg),
        "out_chars": out_chars,
        "models": sorted(models),
        "start": ts(min(times)) if times else None,
        "end": ts(max(times)) if times else None,
        "prompt": prompt,
    }


def task_keys(text: str):
    return {a or b for a, b in TASK_RE.findall(text[:3000])}


def collect(pdir: Path):
    """Все агенты проекта: [(session_id, agent), ...]."""
    agents = []
    for meta_path in pdir.glob("*/subagents/agent-*.meta.json"):
        jsonl = meta_path.with_name(meta_path.name.replace(".meta.json", ".jsonl"))
        if not jsonl.exists():
            continue
        meta = json.loads(meta_path.read_text(encoding="utf-8"))
        info = read_transcript(jsonl)
        info.update(
            role=meta.get("agentType", "?"),
            description=meta.get("description", ""),
            session=meta_path.parent.parent.name,
            keys=task_keys(info["prompt"]) | task_keys(meta.get("description", "")),
        )
        agents.append(info)
    return agents


def total(t):
    return sum(t.values())


def fmt(n):
    return f"{n:,}".replace(",", " ")


def duration(a):
    if not a["start"] or not a["end"]:
        return "—"
    sec = int((a["end"] - a["start"]).total_seconds())
    return f"{sec // 60}м {sec % 60:02d}с"


def add(acc, t):
    for k in FIELDS:
        acc[k] += t[k]


def money(n):
    return fmt(int(round(n)))


def stage(description: str) -> str:
    """Этап — часть описания агента до «:» (`Wave1: …`, `Step 3a: …`), иначе первые два слова."""
    head = description.split(":", 1)[0] if ":" in description else " ".join(description.split()[:2])
    return head.strip() or "—"


def render(key, agents, orchestrators):
    agents = sorted(agents, key=lambda a: a["start"] or datetime.min)
    lines = [f"# Учёт агентов и токенов — {key}", ""]
    lines.append("Собрано `tools/usage/usage.py` из транскриптов Claude Code. Токены: вход / запись кэша / чтение кэша / выход; «итого» — их сумма. Вход и кэш — точно; выход — нижняя граница (транскрипт пишет его в начале ответа, размышления не сохраняются), объём видимого вывода — в символах.")
    lines.append("")
    lines.append("## Агенты")
    lines.append("")
    lines.append("| # | Роль | Поручение | Задача плана | Модель | Старт | Длит. | Вызовов | Вход | Запись кэша | Чтение кэша | Выход ≥ | Вывод, симв. | Итого | Перезапись | Стоимость |")
    lines.append("|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|")
    by_role = defaultdict(lambda: {"n": 0, "t": dict.fromkeys(FIELDS, 0), "cost": 0.0, "rewrite": 0})
    grand = dict.fromkeys(FIELDS, 0)
    grand_cost, grand_rewrite = 0.0, 0
    for i, a in enumerate(agents, 1):
        t = a["tokens"]
        plan_ids = ", ".join(dict.fromkeys(PLAN_ID_RE.findall(a["description"]))) or "—"
        start = a["start"].astimezone().strftime("%m-%d %H:%M") if a["start"] else "—"
        lines.append(
            f"| {i} | {a['role']} | {a['description']} | {plan_ids} | {', '.join(a['models']) or '—'} | {start} | {duration(a)} "
            f"| {a['calls']} | {fmt(t['input_tokens'])} | {fmt(t['cache_creation_input_tokens'])} "
            f"| {fmt(t['cache_read_input_tokens'])} | {fmt(t['output_tokens'])} | {fmt(a['out_chars'])} | **{fmt(total(t))}** "
            f"| {fmt(a['rewrite'])} | {money(a['cost'])} |"
        )
        r = by_role[a["role"]]
        r["n"] += 1
        r["cost"] += a["cost"]
        r["rewrite"] += a["rewrite"]
        add(r["t"], t)
        add(grand, t)
        grand_cost += a["cost"]
        grand_rewrite += a["rewrite"]

    lines += ["", "## По ролям", "", "| Роль | Агентов | Выход ≥ | Итого | Доля | Перезапись | Стоимость | Доля стоимости |", "|---|---|---|---|---|---|---|---|"]
    gt = total(grand) or 1
    gc = grand_cost or 1
    for role, r in sorted(by_role.items(), key=lambda x: -x[1]["cost"]):
        lines.append(f"| {role} | {r['n']} | {fmt(r['t']['output_tokens'])} | {fmt(total(r['t']))} | {total(r['t']) * 100 // gt}% "
                     f"| {fmt(r['rewrite'])} | {money(r['cost'])} | {round(r['cost'] * 100 / gc)}% |")
    lines.append(f"| **все агенты** | {len(agents)} | {fmt(grand['output_tokens'])} | **{fmt(total(grand))}** | 100% "
                 f"| {fmt(grand_rewrite)} | **{money(grand_cost)}** | 100% |")

    by_stage = defaultdict(lambda: {"n": 0, "t": dict.fromkeys(FIELDS, 0), "calls": 0})
    for a in agents:
        st = by_stage[stage(a["description"])]
        st["n"] += 1
        st["calls"] += a["calls"]
        add(st["t"], a["tokens"])
    if len(by_stage) > 1:
        lines += ["", "## По этапам", "", "Этап — часть описания агента до «:». Для оптимизации: где дороже всего и сколько стоит один агент этапа.", ""]
        lines += ["| Этап | Агентов | Вызовов | Итого | На агента | Доля |", "|---|---|---|---|---|---|"]
        for name, st in sorted(by_stage.items(), key=lambda x: -total(x[1]["t"])):
            tt = total(st["t"])
            lines.append(f"| {name} | {st['n']} | {st['calls']} | {fmt(tt)} | {fmt(tt // st['n'])} | {tt * 100 // gt}% |")

    if orchestrators:
        lines += ["", "## Оркестратор", "", "Основная сессия `/dev` целиком, включая работу в ней не по этой задаче.", ""]
        lines.append("| Сессия | Модель | Старт | Длит. | Вызовов | Выход ≥ | Итого | Контекст в конце | Перезапись | Стоимость |")
        lines.append("|---|---|---|---|---|---|---|---|---|---|")
        orch_total = dict.fromkeys(FIELDS, 0)
        orch_cost = 0.0
        for sid, o in orchestrators:
            t = o["tokens"]
            add(orch_total, t)
            orch_cost += o["cost"]
            start = o["start"].astimezone().strftime("%m-%d %H:%M") if o["start"] else "—"
            lines.append(f"| {sid[:8]} | {', '.join(o['models']) or '—'} | {start} | {duration(o)} | {o['calls']} | {fmt(t['output_tokens'])} "
                         f"| {fmt(total(t))} | {fmt(o['context'])} | {fmt(o['rewrite'])} | {money(o['cost'])} |")
        add(orch_total, grand)
        all_cost = orch_cost + grand_cost
        lines += ["", f"**Всего по задаче (агенты + оркестратор): {fmt(total(orch_total))}**, из них выход — не меньше {fmt(orch_total['output_tokens'])}; "
                  f"стоимость — {money(all_cost)} ед. цены входа, оркестратор — {round(orch_cost * 100 / (all_cost or 1))}%."]
    return "\n".join(lines) + "\n"


def main():
    p = argparse.ArgumentParser(description="Учёт агентов и токенов задачи /dev")
    p.add_argument("--project", default=".", help="корень проекта (по умолчанию текущий каталог)")
    p.add_argument("--task", help="ключ задачи (docs/tasks/<ключ>)")
    p.add_argument("--write", action="store_true", help="записать docs/tasks/<ключ>/usage.md")
    p.add_argument("--session", help="ID сессии (имя .jsonl в ~/.claude/projects/<проект>/): все её агенты, без ключа задачи")
    p.add_argument("--since", help="только агенты, начавшиеся не раньше (ISO, например 2026-10-20T13:00)")
    p.add_argument("--match", help="только агенты, у которых описание совпадает с регулярным выражением")
    p.add_argument("--out", help="записать отчёт в файл (для --session)")
    p.add_argument("--context", action="store_true", help="контекст последнего вызова сессии (--session или самой свежей)")
    args = p.parse_args()

    project = Path(args.project).expanduser()
    pdir = project_dir(project)
    if not pdir.is_dir():
        sys.exit(f"нет транскриптов для {project.resolve()} ({pdir})")

    if args.context:
        sessions = sorted(pdir.glob(f"{args.session or ''}*.jsonl"), key=lambda p: p.stat().st_mtime)
        if not sessions:
            sys.exit("сессий не найдено")
        o = read_transcript(sessions[-1])
        print(f"контекст: {fmt(o['context'])} токенов (сессия {sessions[-1].stem[:8]}, вызовов {o['calls']}, стоимость {money(o['cost'])} ед. цены входа)")
        return

    agents = collect(pdir)

    if args.session:
        mine = [a for a in agents if a["session"].startswith(args.session)]
        if args.since:
            since = ts(args.since).astimezone() if "+" in args.since or args.since.endswith("Z") else datetime.fromisoformat(args.since).astimezone()
            mine = [a for a in mine if a["start"] and a["start"] >= since]
        if args.match:
            mine = [a for a in mine if re.search(args.match, a["description"])]
        if not mine:
            sys.exit(f"агентов сессии {args.session} не найдено")
        sid = mine[0]["session"]
        main_jsonl = pdir / f"{sid}.jsonl"
        orchestrators = [(sid, read_transcript(main_jsonl))] if main_jsonl.exists() and not (args.since or args.match) else []
        out = render(f"сессия {sid[:8]}", mine, orchestrators)
        if args.out:
            Path(args.out).write_text(out, encoding="utf-8")
            print(f"записано: {args.out}")
        else:
            print(out)
        return

    if not args.task:
        tasks = defaultdict(lambda: [0, 0])
        for a in agents:
            for k in a["keys"]:
                tasks[k][0] += 1
                tasks[k][1] += total(a["tokens"])
        if not tasks:
            print("задач с агентами dev-framework не найдено")
        for k, (n, t) in sorted(tasks.items()):
            print(f"{k}\tагентов: {n}\tтокенов: {fmt(t)}")
        return

    mine = [a for a in agents if args.task in a["keys"]]
    if not mine:
        sys.exit(f"агентов задачи {args.task} не найдено")
    orchestrators = []
    for sid in sorted({a["session"] for a in mine}):
        main_jsonl = pdir / f"{sid}.jsonl"
        if main_jsonl.exists():
            orchestrators.append((sid, read_transcript(main_jsonl)))
    out = render(args.task, mine, orchestrators)

    if args.write:
        dst = project / "docs" / "tasks" / args.task / "usage.md"
        dst.parent.mkdir(parents=True, exist_ok=True)
        dst.write_text(out, encoding="utf-8")
        print(f"записано: {dst}")
    else:
        print(out)


if __name__ == "__main__":
    main()
