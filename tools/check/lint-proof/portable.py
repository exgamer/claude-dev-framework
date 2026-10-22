#!/usr/bin/env python3
"""Доказательство для `tools/check/portable.py`: ловит путь в домашнюю папку, значения этой машины и секреты;
заглушки, `~/`, `$HOME`, путь в контейнере, имя формата ключа и тестовые ключи в `testdata/` — не находки;
чистая копия — 0; из подпапки проверяется весь репозиторий; вне git — код 2.

Запуск из корня репозитория: python3 tools/check/lint-proof/portable.py — во временном git-репозитории,
репозиторий фреймворка не меняется. Значения собираются из частей, чтобы сам файл не был находкой.
"""
import getpass
import os
import shutil
import subprocess
import sys
import tempfile

ROOT = os.getcwd()
HOME = os.path.expanduser("~")
USER = getpass.getuser()
EMAIL = "proof.user" + "@" + "example.org"  # в git временного репозитория — независимо от настроек машины
BEGIN = "-----BEGIN " + "PRIVATE KEY-----"
BODY = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7"
CASES = [  # (имя, файл, содержимое, что должно быть в выводе | None — не находка)
    ("путь macOS", "rules.md", "запуск: /" + "Users/alice/proj/run.sh", "путь в домашнюю папку"),
    ("путь Linux", "rules.md", "лог в /" + "home/bob/.cache/x", "путь в домашнюю папку"),
    ("путь Windows", "rules.md", "C:" + "\\Users\\carol\\proj", "путь в домашнюю папку"),
    ("путь Windows с \\\\", "cfg.json", '"C:' + "\\\\Users\\\\carol\\\\proj" + '"', "путь в домашнюю папку"),
    ("путь WSL", "rules.md", "/mnt/c/" + "Users/dave/proj", "путь в домашнюю папку"),
    ("домашняя папка этой машины", "graph.html", "<title>graphify - " + HOME + "/x</title>", "домашняя папка этой машины"),
    ("почта git этой машины", "README.md", "автор: " + EMAIL, "почта git этой машины"),
    ("токен GitLab", "cfg.md", "token: " + "glpat" + "-" + "a" * 20, "токен GitLab"),
    ("токен GitHub", "cfg.md", "gh" + "p_" + "A" * 36, "токен GitHub"),
    ("токен Slack", "cfg.md", "xo" + "xb-" + "1234567890-abcdef", "токен Slack"),
    ("ключ AWS", "cfg.md", "AK" + "IA" + "ABCDEFGHIJKLMNOP", "ключ AWS"),
    ("ключ Google", "cfg.md", "AI" + "za" + "B" * 35, "ключ Google"),
    ("ключ Anthropic", "cfg.md", "sk-" + "ant-" + "a" * 24, "ключ Anthropic"),
    ("закрытый ключ с телом", "key.pem", BEGIN + "\n" + BODY + "\n", "закрытый ключ"),
    ("заглушка <имя>", "rules.md", "путь /" + "Users/<имя>/proj", None),
    ("путь от $HOME", "rules.md", "$" + "HOME/.claude/dev-framework", None),
    ("путь от ~/", "rules.md", "~/.claude/dev-framework/tools", None),
    ("URL с /home/", "rules.md", "https://example.com/" + "home/x", None),
    ("путь в контейнере", "Dockerfile", "WORKDIR /" + "home/app", None),
    ("имя формата ключа", "rules.md", "литерал `" + BEGIN + "` в коде", None),
    ("закрытый ключ в JSON", "sa.json", '{"private_key": "' + BEGIN + "\\n" + BODY + '\\n"}', "закрытый ключ"),
    ("тело ключа в кавычках YAML", "cfg.yml", 'key: "' + BEGIN + '\n  ' + BODY + '"', "закрытый ключ"),
    ("ключ в коде Go, строки через +", "key.go", 'const k = "' + BEGIN + '\\n" +\n\t"' + BODY + '\\n"', "закрытый ключ"),
    ("ключ в массиве JSON", "key.json", '["' + BEGIN + '",\n "' + BODY + '"]', "закрытый ключ"),
    ("зашифрованный ключ", "key.pem", "-----BEGIN " + "RSA PRIVATE KEY-----" + "\n" + "Proc-Type: 4,ENCRYPTED\n", "закрытый ключ"),
    ("ключ в testdata", "pkg/testdata/k.pem", BEGIN + "\n" + BODY + "\n", None),
    ("Windows %USERNAME%", "rules.md", "C:" + "\\Users\\%USERNAME%\\proj", None),
    ("общая папка macOS", "rules.md", "/" + "Users/Shared/x", None),
    ("общая папка Windows", "rules.md", "C:" + "\\Users\\Public\\x", None),
]
if len(USER) >= 6:  # короткое имя скрипт не ищет — случай не про эту машину
    CASES.append(("имя пользователя этой машины", "notes.md", "запускал " + USER + " вручную", "имя пользователя этой машины"))
    CASES.append(("имя в папке проекта Claude Code", "notes.md", "~/.claude/projects/-" + "Users-" + USER + "-x", "имя пользователя этой машины"))
else:
    print("ПРОПУСК имя пользователя этой машины: короче 6 символов")


def repo(path, content):
    dst = tempfile.mkdtemp(prefix="portableproof-")
    os.makedirs(os.path.join(dst, "tools/check"))
    shutil.copy(os.path.join(ROOT, "tools/check/portable.py"), os.path.join(dst, "tools/check/portable.py"))
    if path:
        os.makedirs(os.path.dirname(os.path.join(dst, path)), exist_ok=True)
        with open(os.path.join(dst, path), "w", encoding="utf-8") as f:
            f.write(content + "\n")
    subprocess.run(["git", "init", "-q"], cwd=dst, check=True)
    subprocess.run(["git", "config", "user.email", EMAIL], cwd=dst, check=True)
    return dst


def run(dst):
    return subprocess.run([sys.executable, "tools/check/portable.py"], cwd=dst, capture_output=True, text=True)


def main():
    fails = 0
    for name, path, content, expect in CASES:
        dst = repo(path, content)
        res = run(dst)
        ok = (res.returncode == 1 and f"{path}:1: {expect}" in res.stdout) if expect else res.returncode == 0
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name + ("" if ok else "\n" + res.stdout))
        shutil.rmtree(dst)
    dst = repo(None, None)
    res = run(dst)
    print("чистая копия:", res.stdout.strip().splitlines()[-1])
    fails += res.returncode != 0
    for name, fname, data in (("двоичный файл — пропуск", "img.bin", b"\0\0/" + b"Users/alice/x\n"),
                              ("файл из .gitignore — пропуск", "local.json", b"/" + b"Users/alice/x\n")):
        with open(os.path.join(dst, fname), "wb") as f:
            f.write(data)
        with open(os.path.join(dst, ".gitignore"), "w", encoding="utf-8") as f:
            f.write("local.json\n")
        res = run(dst)
        ok = res.returncode == 0
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name + ("" if ok else "\n" + res.stdout))
        os.remove(os.path.join(dst, fname))
    image = "vscode"
    with open(os.path.join(dst, "notes.md"), "w", encoding="utf-8") as f:
        f.write("devcontainer: " + image + ", WORKDIR /" + "home/" + image + "\n")
    res = subprocess.run([sys.executable, "tools/check/portable.py"], cwd=dst, capture_output=True, text=True,
                         env={**os.environ, "USER": image, "LOGNAME": image, "HOME": "/" + "home/" + image})
    ok = res.returncode == 0
    fails += not ok
    print(("OK    " if ok else "MISS  ") + "пользователь образа (devcontainer, CI) — не значение машины" + ("" if ok else "\n" + res.stdout))
    os.remove(os.path.join(dst, "notes.md"))
    os.makedirs(os.path.join(dst, "sub"))
    with open(os.path.join(dst, "leak.md"), "w", encoding="utf-8") as f:
        f.write("/" + "Users/alice/x\n")
    res = subprocess.run([sys.executable, os.path.join(dst, "tools/check/portable.py")], cwd=os.path.join(dst, "sub"),
                         capture_output=True, text=True)
    ok = res.returncode == 1 and "leak.md" in res.stdout
    fails += not ok
    print(("OK    " if ok else "MISS  ") + "запуск из подпапки проверяет весь репозиторий")
    shutil.rmtree(dst)
    dst = tempfile.mkdtemp(prefix="portableproof-nogit-")
    res = subprocess.run([sys.executable, os.path.join(ROOT, "tools/check/portable.py")], cwd=dst,
                         capture_output=True, text=True, env={**os.environ, "GIT_CEILING_DIRECTORIES": os.path.dirname(dst)})
    ok = res.returncode == 2
    fails += not ok
    print(("OK    " if ok else "MISS  ") + "вне git — код 2")
    shutil.rmtree(dst)
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
