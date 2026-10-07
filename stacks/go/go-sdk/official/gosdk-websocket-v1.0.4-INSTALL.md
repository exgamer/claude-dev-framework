## Установка

Пакет размещён на приватном GitLab — перед установкой нужно настроить доступ.

**1. Personal Access Token**

Создать на `https://git.mpinnovations.kz` в **Settings → Access Tokens** с правом `read_repository`.

**2. ~/.gitconfig** — для git-операций (все ОС)

```bash
git config --global url."https://oauth2:YOUR_TOKEN@git.mpinnovations.kz/".insteadOf "https://git.mpinnovations.kz/"
```

**3. Файл netrc** — для HTTP-запросов Go

Go выполняет отдельный HTTP-запрос для обнаружения VCS, и `~/.gitconfig` в этот момент не применяется.

<details>
<summary>macOS / Linux</summary>

```bash
echo "machine git.mpinnovations.kz login oauth2 password YOUR_TOKEN" >> ~/.netrc
chmod 600 ~/.netrc
```

</details>

<details>
<summary>Windows (PowerShell)</summary>

На Windows Go читает `%USERPROFILE%\_netrc`:

```powershell
Add-Content "$env:USERPROFILE\_netrc" "machine git.mpinnovations.kz login oauth2 password YOUR_TOKEN"
```

</details>

**4. GOPRIVATE** — чтобы Go не обращался к публичному прокси (все ОС)

```bash
go env -w GOPRIVATE=git.mpinnovations.kz/*
```

**5. Установка**

```bash
go get git.mpinnovations.kz/mps/go-packages/gosdk-websocket
```


### Как работает в  Docker?

2) Docker файл прописать:
    1) <code>ENV GOPRIVATE=gitlab.almanit.kz/jmart/*</code>
    2) <code>RUN git config --global url."https://gitlab-ci-token:${READ_JMART_PROJECT_TOKEN}@gitlab.almanit.kz/".insteadOf "https://gitlab.almanit.kz/"
       </code>   
       3)Сделать запрос команде Devops о том, чтобы они добавили переменную READ_JMART_PROJECT_TOKEN в команду создания образа