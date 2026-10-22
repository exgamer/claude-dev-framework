#!/usr/bin/env python3
"""Продолжение команды фреймворка после /clear или в новой сессии — без повторного «/dev <ключ>».

Координатор (/dev, /dev-fix, /dev-mr, /dev-rule) ставит метку, когда определил задачу, и снимает, когда
закончил. Метка — <проект>/.claude/df-resume.json (личная, в git не идёт: install.sh — в .git/info/exclude).
Хук SessionStart (startup|clear) подкладывает новой сессии незаконченные команды: пользователь пишет
«дальше» — сессия вызывает тот же скилл с теми же аргументами и проходит его Фазу 0 заново.
Тот же хук в проекте, где тур ещё не предлагали (нет <проект>/.claude/df-intro-shown), просит первой строкой
первого ответа предложить /dev-intro и ставит эту отметку — подсказка одна на проект.

  resume.py set <команда> <задача> [--flags "<флаги>"]   — метка: /<команда> [флаги] <задача>
  resume.py done <команда> <задача>                      — снять метку
  resume.py list                                         — незаконченные команды проекта
  (у set/done/list — --project <папка>, если текущая папка не в проекте, например в репозитории фреймворка)
  resume.py hook                                         — для SessionStart: stdin — JSON хука, stdout — контекст
  resume.py install <проект>                             — хук в <проект>/.claude/settings.local.json
  resume.py uninstall <проект>                           — убрать хук

Проект — CLAUDE_PROJECT_DIR, иначе ближайшая вверх от текущей папки с .claude/skills/dev (установка фреймворка),
иначе корень git, иначе текущая папка (хук — ещё cwd из входа хука). Метки старше 14 дней не показываются
и удаляются при следующей записи.
"""
import argparse
import json
import os
import subprocess
import sys
from datetime import datetime, timedelta, timezone
from pathlib import Path

MARK = Path(".claude") / "df-resume.json"
INTRO_MARK = Path(".claude") / "df-intro-shown"
MAX_AGE = timedelta(days=14)
# нет ссылки ~/.claude/dev-framework (фреймворк удалён) — хук молчит, а не падает на каждом старте
HOOK_CMD = ('f="$HOME/.claude/dev-framework/tools/resume/resume.py"; '
            '[ ! -f "$f" ] || python3 "$f" hook')
COMMANDS = ("dev", "dev-fix", "dev-mr", "dev-rule")


def now():
    return datetime.now(timezone.utc)


def project_dir(start=None):
    if os.environ.get("CLAUDE_PROJECT_DIR"):
        return Path(os.environ["CLAUDE_PROJECT_DIR"])
    start = Path(start or Path.cwd()).resolve()
    for d in (start, *start.parents):
        if (d / ".claude" / "skills" / "dev").exists():
            return d
    try:
        top = subprocess.run(["git", "-C", str(start), "rev-parse", "--show-toplevel"],
                             capture_output=True, text=True, check=True).stdout.strip()
        return Path(top) if top else start
    except (OSError, subprocess.CalledProcessError):
        return start


def target(args):
    return Path(args.project).resolve() if args.project else project_dir()


def same_task(project, task):
    """Путь внутри проекта — относительно проекта: абсолютный и относительный путь к одному логу — одна метка."""
    p = Path(task)
    if p.is_absolute():
        try:
            return str(p.resolve().relative_to(project.resolve()))
        except ValueError:
            return task
    return os.path.normpath(task)


def load(project):
    try:
        data = json.loads((project / MARK).read_text())
    except (OSError, ValueError):
        return []
    fresh = []
    for e in data if isinstance(data, list) else []:
        try:
            ok = (e["command"] in COMMANDS and isinstance(e["task"], str) and e["task"]
                  and isinstance(e.get("flags", ""), str))
            if ok and timedelta(0) <= now() - datetime.fromisoformat(e["time"]) <= MAX_AGE:
                fresh.append(e)
        except (KeyError, TypeError, ValueError):
            continue
    return fresh


def save(project, entries):
    path = project / MARK
    if not entries:
        path.unlink(missing_ok=True)
        return
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(".tmp")
    tmp.write_text(json.dumps(entries, ensure_ascii=False, indent=2) + "\n")
    tmp.replace(path)


def line(e):
    return " ".join(x for x in ("/" + e["command"], e.get("flags", ""), e["task"]) if x)


def age(e):
    minutes = int((now() - datetime.fromisoformat(e["time"])).total_seconds() // 60)
    if minutes < 60:
        return f"{minutes} мин назад"
    if minutes < 48 * 60:
        return f"{minutes // 60} ч назад"
    return f"{minutes // (24 * 60)} дн назад"


def context(entries):
    rows = "\n".join(f"- `{line(e)}` — метка {age(e)}" for e in entries)
    pick = ("Команда одна — продолжай её." if len(entries) == 1
            else "Команд несколько — спроси одной строкой, какую продолжать.")
    return (
        "dev-framework: в этом проекте не закончена работа (метка `.claude/df-resume.json`):\n"
        f"{rows}\n"
        "Если первое сообщение пользователя — просьба продолжить («дальше», «продолжай», «го», «ок» и т. п.) — "
        "это продолжение этой команды: вызови её скилл (Skill, имя без «/», аргументы — остаток строки), как если бы "
        f"пользователь сам набрал её; Фаза 0 (Шаг 1) скилла — заново, затем — с места остановки по артефактам задачи, как велит скилл. {pick} "
        "Пользователь пишет о другом — не продолжай и не напоминай. Пользователь говорит, что задача закончена "
        "или брошена, — `python3 \"$HOME/.claude/dev-framework/tools/resume/resume.py\" done <команда> <задача> "
        "--project <корень проекта>`."
    )


def intro():
    return (
        "dev-framework: в этом проекте тур по фреймворку ещё не предлагали. Один раз — первой строкой самого первого "
        "текста в этой сессии, о чём бы ни было сообщение, — «Можно пройти тур по возможностям dev-framework: "
        "`/dev-intro`.», затем — ответ как обычно. Строка уже есть в разговоре — не повторяй её: ни в итоговом "
        "тексте того же ответа после инструментов и агентов, ни в следующих ответах. Больше в этом проекте не предлагай."
    )


def cmd_set(args):
    project = target(args)
    args.task = same_task(project, args.task)
    entries = [e for e in load(project) if (e["command"], e["task"]) != (args.command, args.task)]
    entry = {"command": args.command, "task": args.task, "time": now().isoformat(timespec="seconds")}
    if args.flags:
        entry["flags"] = args.flags.strip()
    entries.append(entry)
    save(project, entries)
    print(f"метка: {line(entry)}")


def cmd_done(args):
    project = target(args)
    args.task = same_task(project, args.task)
    entries = load(project)
    left = [e for e in entries if (e["command"], e["task"]) != (args.command, args.task)]
    save(project, left)
    print("метка снята" if len(left) < len(entries) else "метки не было")


def cmd_list(args):
    entries = load(target(args))
    for e in entries:
        print(f"{line(e)}\t{age(e)}")
    if not entries:
        print("незаконченных команд нет")


def cmd_hook(_):
    try:
        data = json.load(sys.stdin)
    except ValueError:
        data = {}
    if not isinstance(data, dict):
        data = {}
    cwd = data.get("cwd")
    project = project_dir(cwd if isinstance(cwd, str) else None)
    parts = []
    mark = project / INTRO_MARK
    # скилл тура — по ссылке в проекте: после обновления фреймворка без install.sh его ещё нет, отметку не тратим
    if (project / ".claude" / "skills" / "dev-intro").exists() and not mark.exists():
        try:
            mark.touch()  # отметка — сразу: подсказка не повторится в следующих сессиях
            parts.append(intro())
        except OSError:
            pass
    entries = load(project)
    if entries:
        parts.append(context(entries))
    if parts:
        print(json.dumps({"hookSpecificOutput": {"hookEventName": "SessionStart",
                                                 "additionalContext": "\n\n".join(parts)}}, ensure_ascii=False))


def cmd_install(args):
    path = Path(args.project).resolve() / ".claude" / "settings.local.json"
    try:
        settings = json.loads(path.read_text()) if path.exists() else {}
    except ValueError:
        settings = None
    hooks = settings.setdefault("hooks", {}) if isinstance(settings, dict) else None
    groups = hooks.setdefault("SessionStart", []) if isinstance(hooks, dict) else None
    if not isinstance(groups, list) or not all(isinstance(g, dict) and isinstance(g.get("hooks", []), list) for g in groups):
        sys.exit(f"{path}: не разобрать (нужен объект с hooks.SessionStart — списком) — хук не добавлен, поправьте файл")
    if any(isinstance(h, dict) and h.get("command") == HOOK_CMD for g in groups for h in g.get("hooks", [])):
        print(f"ok      хук продолжения — {path}")
        return
    groups.append({"matcher": "startup|clear", "hooks": [{"type": "command", "command": HOOK_CMD}]})
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(settings, ensure_ascii=False, indent=2) + "\n")
    print(f"hook    продолжение команды после /clear и в новой сессии — {path}")


def cmd_uninstall(args):
    path = Path(args.project).resolve() / ".claude" / "settings.local.json"
    try:
        settings = json.loads(path.read_text())
        groups = settings["hooks"]["SessionStart"]
        assert isinstance(groups, list) and all(isinstance(g, dict) and isinstance(g.get("hooks", []), list) for g in groups)
    except (OSError, ValueError, KeyError, TypeError, AssertionError):
        return
    if not any(isinstance(h, dict) and h.get("command") == HOOK_CMD for g in groups for h in g.get("hooks", [])):
        return
    left = []
    for g in groups:
        g["hooks"] = [h for h in g.get("hooks", []) if not (isinstance(h, dict) and h.get("command") == HOOK_CMD)]
        if g["hooks"]:
            left.append(g)
    if left:
        settings["hooks"]["SessionStart"] = left
    elif "SessionStart" in settings.get("hooks", {}):
        del settings["hooks"]["SessionStart"]
        if not settings["hooks"]:
            del settings["hooks"]
    if settings:
        path.write_text(json.dumps(settings, ensure_ascii=False, indent=2) + "\n")
    else:
        path.unlink()  # файл создал install — пустым не оставлять
    print(f"removed хук продолжения — {path}")


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = p.add_subparsers(dest="cmd", required=True)
    s = sub.add_parser("set")
    s.add_argument("command", choices=COMMANDS)
    s.add_argument("task")
    s.add_argument("--flags", default="")
    s.set_defaults(func=cmd_set)
    d = sub.add_parser("done")
    d.add_argument("command", choices=COMMANDS)
    d.add_argument("task")
    d.set_defaults(func=cmd_done)
    ls = sub.add_parser("list")
    ls.set_defaults(func=cmd_list)
    for sp in (s, d, ls):
        sp.add_argument("--project", default=None)
    sub.add_parser("hook").set_defaults(func=cmd_hook)
    i = sub.add_parser("install")
    i.add_argument("project")
    i.set_defaults(func=cmd_install)
    u = sub.add_parser("uninstall")
    u.add_argument("project")
    u.set_defaults(func=cmd_uninstall)
    argv = sys.argv[1:]
    if "--flags" in argv[:-1]:  # значение флагов само начинается с «--» — argparse не примет его отдельным словом
        i = argv.index("--flags")
        argv[i:i + 2] = ["--flags=" + argv[i + 1]]
    args = p.parse_args(argv)
    args.func(args)


if __name__ == "__main__":
    main()
