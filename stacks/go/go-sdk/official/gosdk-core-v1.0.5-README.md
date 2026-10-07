# gosdk-core

`gosdk-core` — это **легковесный core-SDK на Go** для построения сервисов и приложений с единым и предсказуемым подходом к архитектуре.

Он помогает стандартизировать:
- инициализацию приложения
- управление жизненным циклом
- dependency injection
- работу с конфигурацией
- обработку ошибок
- логирование и отладку

Подходит для **микросервисов, внутренних сервисов и backend-приложений**, где важны порядок, расширяемость и контроль над зависимостями.

---

## ✨ Ключевые возможности

- 🧠 **Application lifecycle**
    - единая точка инициализации
    - контролируемый старт и graceful shutdown

- 🧩 **Dependency Injection**
    - типобезопасная регистрация зависимостей
    - поддержка фабрик
    - минималистичный DI без магии

- 🧱 **Модульная архитектура**
    - изолированные бизнес-модули
    - слабая связанность между частями приложения
    - явная регистрация зависимостей

- ⚙️ **Конфигурация**
    - загрузка env-переменных
    - базовые конфиги приложения

- ❗ **Ошибки**
    - единая модель ошибок
    - удобная работа с доменными и инфраструктурными ошибками
    - репортинг некритичных ошибок во внешний трекер (Sentry и т.п.) без прерывания выполнения

- 📝 **Логирование и debug**
    - централизованный логгер
    - debug-collector для диагностики и трассировки

---

## 📦 Установка

### 1. Настройка окружения (один раз на машине)

Так как модуль находится в приватном GitLab-репозитории, нужно сообщить Go об этом:

```bash
go env -w GOPRIVATE=git.mpinnovations.kz
go env -w GONOSUMDB=git.mpinnovations.kz
```

### 2. Настройка авторизации

Создайте Personal Access Token в GitLab: **Profile → Access Tokens** с правами `read_repository`.

**Настройте git** (подставьте ваш токен — работает на всех ОС):

```bash
git config --global url."https://oauth2:ТОКЕН@git.mpinnovations.kz/".insteadOf "https://git.mpinnovations.kz/"
```

**Настройте netrc** для HTTP-запросов Go:

<details>
<summary>macOS / Linux</summary>

```bash
echo "machine git.mpinnovations.kz login oauth2 password ТОКЕН" > ~/.netrc
chmod 600 ~/.netrc
```

</details>

<details>
<summary>Windows (PowerShell)</summary>

```powershell
"machine git.mpinnovations.kz login oauth2 password ТОКЕН" | Out-File "$env:USERPROFILE\_netrc" -Encoding ascii
```

</details>

### 3. Подключить модуль

```bash
go get git.mpinnovations.kz/mps/go-packages/gosdk-core@v1.0.0
```

---

## 📚 Документация

- 📘 **Application Core**
    - [Usage Guide](pkg/app/README.MD)

- 🧩 **Dependency Injection**
    - [Как работает DI-контейнер](pkg/di/README.MD)
    - [Что доступно в DI из коробки](pkg/di/DI_FUNCTIONS_README.MD)

- ❗ **Работа с ошибками**
    - [Exception Guide](pkg/exception/README.MD)

- 🐞 **Debug Collector**
    - [Debug README](pkg/debug/README.MD)
    - 
- 📘 **Logger**
  - [Как юзать логгер](pkg/logger/README.md)

-    **Переменные окружения**
  - [Переменные окружения](pkg/config/ENV_README.md)

- 🚨 **Error Reporter**
  - [Как репортить некритичные ошибки в Sentry/etc](pkg/errorreporter/README.md)
---
- 📘 **AppException**
  - [Как юзать исключения](pkg/exception/README.MD)

---

## 🧪 Рекомендации по использованию

✔ **Рекомендуется**
- группировать код по бизнес-модулям
- регистрировать зависимости внутри модулей
- использовать DI через интерфейсы
- держать `app` как инфраструктурный слой

✖ **Не рекомендуется**
- хранить бизнес-логику в `app`
- использовать DI-контейнер как service-locator по всему проекту
- создавать жёсткие зависимости между модулями

---

## 🎯 Для кого этот SDK

`gosdk-core` подойдёт если вы:
- строите Go-микросервисы
- хотите единый стандарт инициализации
- устали от хаотичного `main.go`
- не хотите тяжёлых фреймворков
- цените контроль и прозрачность архитектуры

---

## 📄 License

MIT
