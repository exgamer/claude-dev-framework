#!/usr/bin/env python3
"""Переносимость: в репозитории фреймворка нет ничего, что привязано к машине автора, — ни в правилах, ни в скриптах,
ни в сгенерированных файлах (граф, справочники). Фреймворк ставят себе другие: путь с чужой домашней папкой
у них не работает, а почта и токены попадают в общий репозиторий.

Запуск: python3 tools/check/portable.py (из любой папки репозитория — проверяется весь).
Код возврата: 0 — чисто, 1 — есть находки, 2 — проверено не всё (нет git).

Смотрит файлы git (отслеживаемые и новые, кроме .gitignore), текстовые:
- абсолютный путь в домашнюю папку — `/Users/<имя>`, `/home/<имя>`, `C:\\Users\\<имя>`, `/mnt/c/Users/<имя>`;
  заглушка `<…>`, `%USERNAME%`, `C:\\Users\\Public` — можно; пользователь образа (`/home/app`, `/home/node`, …) — путь в контейнере, можно;
  `/Users/Shared` — общая папка macOS, можно;
- домашняя папка, имя пользователя ОС и почта git текущей машины — в любом виде (пользователь образа, CI, devcontainer — нет);
- токены и ключи: GitLab, GitHub, Slack, AWS, Google, Anthropic, закрытый ключ PEM с телом — и в строке JSON
  с `\\n` (одно имя формата — можно);
  в `testdata/` (ключи и токены для тестов) секреты не ищутся, пути — ищутся.
Не ловит (смотрит ревью глазами): имена контейнеров и папки проектов одной машины.
Как надо: путь от корня репозитория, `{DF}/`, `~/.claude/…`, `$HOME`, заглушка `<проект>`; своё значение машины —
в файле из .gitignore или переменной окружения (как `tools/lint/.php-containers.json`).
"""
import getpass
import os
import re
import subprocess
import sys

# пользователи образов, CI и devcontainer — не человек: путь /home/<они> — в контейнере, их имя — не значение машины
IMAGE_USER_NAMES = {"app", "appuser", "node", "www-data", "user", "runner", "ubuntu", "debian", "vscode", "jenkins",
                    "gitlab", "gitlab-runner", "root", "ec2-user"}
IMAGE_USERS = "(?:" + "|".join(re.escape(u) for u in sorted(IMAGE_USER_NAMES, key=len, reverse=True)) + r")(?![\w-])"
PATHS = [
    ("путь в домашнюю папку", re.compile(r"(?<![\w$}])/(?:Users|home)/(?![<{$*])(?!" + IMAGE_USERS + r"|Shared\b)[\w.-]+")),
    ("путь в домашнюю папку", re.compile(r"/mnt/[a-z]/Users/(?![<{$*])[\w.-]+", re.I)),
    ("путь в домашнюю папку", re.compile(r"\b[A-Za-z]:\\{1,2}Users\\{1,2}(?![<{%*])(?!(?:Public|Default)\b)[\w.-]+", re.I)),
]
SECRETS = [
    ("токен GitLab", re.compile(r"glpat-[\w-]{20,}")),
    ("токен GitHub", re.compile(r"\b(?:gh[pousr]_[A-Za-z0-9]{36,}|github_pat_\w{22,})")),
    ("токен Slack", re.compile(r"\bxox[abpors]-[\w-]{10,}")),
    ("ключ AWS", re.compile(r"\bAKIA[0-9A-Z]{16}\b")),
    ("ключ Google", re.compile(r"\bAIza[0-9A-Za-z_-]{35}\b")),
    ("ключ Anthropic", re.compile(r"\bsk-ant-[\w-]{20,}")),
    ("закрытый ключ", re.compile(r"-----BEGIN (?:[A-Z]+ )*PRIVATE KEY-----(?:(?:\\r)?\\n)?[\"'`]?\s*[,+]?\s*$")),  # тело — со следующей строки (и в коде: "…\n" +)
    ("закрытый ключ", re.compile(r"PRIVATE KEY-----\\n(?:Proc-Type|[A-Za-z0-9+/]{40,})")),  # в строке JSON: ключ сервисного аккаунта
]
PEM_BODY = re.compile(r"^(?:[A-Za-z0-9+/=]{40,}|Proc-Type:)")  # тело (в YAML — и в кавычках) или шапка зашифрованного ключа


def machine_values():
    """Значения этой машины — ловятся и там, где общий шаблон не сработает (другой вид пути, имя в тексте)."""
    vals = []
    home = os.path.expanduser("~")
    if len(home) > 6 and os.path.basename(home) not in IMAGE_USER_NAMES:
        vals.append(("домашняя папка этой машины", re.compile(re.escape(home))))
    try:
        user = getpass.getuser()
    except (OSError, KeyError):
        user = ""
    if len(user) >= 6 and user not in IMAGE_USER_NAMES:  # короткое имя (dev, admin) — обычное слово, ложные находки
        vals.append(("имя пользователя этой машины", re.compile(r"(?<![A-Za-z0-9_])" + re.escape(user) + r"(?![A-Za-z0-9_])")))
    email = subprocess.run(["git", "config", "user.email"], capture_output=True, text=True).stdout.strip()
    if "@" in email:
        vals.append(("почта git этой машины", re.compile(re.escape(email), re.I)))
    return vals


def files():
    out = subprocess.run(["git", "ls-files", "-z", "--cached", "--others", "--exclude-standard"],
                         capture_output=True, text=True, check=True).stdout
    return sorted({f for f in out.split("\0") if f and os.path.isfile(f)})


def read_text(f):
    with open(f, "rb") as fh:
        data = fh.read()
    if b"\0" in data[:8192]:
        return None  # двоичный файл
    return data.decode("utf-8", errors="replace")


def main():
    try:
        top = subprocess.run(["git", "rev-parse", "--show-toplevel"], capture_output=True, text=True, check=True)
        os.chdir(top.stdout.strip())
        machine = machine_values()
        paths = files()
    except (OSError, subprocess.CalledProcessError) as e:
        print(f"переносимость: проверено не всё — нужен git-репозиторий ({e})")
        return 2
    problems = []
    for f in paths:
        text = read_text(f)
        if text is None:
            continue
        lines = text.splitlines()
        secrets = [] if "testdata" in f.split("/") else SECRETS
        for n, line in enumerate(lines, 1):
            for name, rx in PATHS + secrets + machine:
                m = rx.search(line)
                if not m:
                    continue
                pem_line = name == "закрытый ключ" and not m.group(0).startswith("PRIVATE")
                if not pem_line or n < len(lines) and PEM_BODY.match(lines[n].strip().strip("\"'")):
                    problems.append(f"{f}:{n}: {name}: {m.group(0)[:60]}")
    for p in dict.fromkeys(problems):
        print(p)
    print(f"переносимость: находок: {len(set(problems))}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
