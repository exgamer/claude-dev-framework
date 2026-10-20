# internal/app/app.go — инициализация приложения

Файл лежит в: `internal/app/app.go`

Содержит три вещи:
1. Объявление типа `App` с эмбедом `*app.App`
2. `RegisterAndInitKernels(...)` — подключение инфраструктурных ядер
3. `RegisterAndInitModules(...)` — регистрация бизнес-модулей

Никакой бизнес-логики, никакой конфигурации — только сборка.

## Доступные Kernels

| Kernel           | Импорт                        | Назначение                                              |
|------------------|-------------------------------|---------------------------------------------------------|
| `PostgresKernel` | `gosdk-postgres-core/pkg/app` | Подключение к PostgreSQL, регистрация GORM клиента в DI |
| `HttpKernel`     | `gosdk-http-core/pkg/app`     | Gin роутер, регистрация в DI                            |
| `RedisKernel`    | `gosdk-redis-core/pkg/app`    | Redis клиент, регистрация в DI                          |
| `RabbitKernel`   | `gosdk-rabbit-core/pkg/app`   | AMQP соединение, consumers/publishers registry в DI     |
| `SentryKernel`   | `gosdk-sentry-core/pkg/app`   | Отправка ошибок `errorreporter` в Sentry (`SENTRY_DSN`); отдельный модуль — без него события молча не уходят (`capabilities.md`) |

## Консольное приложение

`cmd/console` — отдельный бинарник со своим набором ядер (`PostgresKernel`, `RabbitKernel` с `EnablePublisher()` при отправке в брокер, `ConsoleKernel`) и модулями консоли (`&outbox_tariff_event.Module{Console: consoleKernel}`); `HttpKernel` и consumer-ы сервиса туда не входят. Путь команд, модуль консоли, `commands_factory.go`, миграции — `../structure.md`, «Entrypoint Layer»; эталон — `examples/tariff` (README, `app/bootstrap/billing/outbox_tariff_event/`).

## Порядок модулей важен

Если модуль B берёт сервис модуля A через DI — A регистрируется первым (`&auth.Module{}` перед `&tariff.Module{}` в `examples/tariff/internal/app/app.go`). Допустимые формы зависимости и что запрещено — `../structure.md`, «Зависимости модулей bootstrap».

## Шаблон

```go
package app

import (
    "git.example.com/.../internal/app/bootstrap/billing/tariff"
    "github.com/exgamer/gosdk-core/pkg/app"
    http "github.com/exgamer/gosdk-http-core/pkg/app"
    postgresCore "github.com/exgamer/gosdk-postgres-core/pkg/app"
    rediscore "github.com/exgamer/gosdk-redis-core/pkg/app"
	rabbitcore "github.com/exgamer/gosdk-rabbit-core/pkg/app"
    sentrycore "github.com/exgamer/gosdk-sentry-core/pkg/app"
)

type App struct {
    *app.App
}

func NewApp() (*App, error) {
    appInstance := &App{
        App: app.NewApp(),
    }

    err := appInstance.RegisterAndInitKernels(
        &sentrycore.SentryKernel{},
        &postgresCore.PostgresKernel{},
        &http.HttpKernel{},
        &rediscore.RedisKernel{},
		rabbitcore.NewRabbitKernel().EnableConsumer().EnablePublisher(), // просто для примера работы с несколькими ядрами которые запускаются
    )
    if err != nil {
        return nil, err
    }

    err = appInstance.RegisterAndInitModules(
        &tariff.Module{}, // регистрирует модуль 
    )
    if err != nil {
        return nil, err
    }

    return appInstance, nil
}
```
