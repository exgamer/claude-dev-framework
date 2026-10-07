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

## Порядок модулей важен

Если модуль B зависит от сервиса модуля A через DI — A должен быть зарегистрирован первым. Но делать модули зависимыми запрещается

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
)

type App struct {
    *app.App
}

func NewApp() (*App, error) {
    appInstance := &App{
        App: app.NewApp(),
    }

    err := appInstance.RegisterAndInitKernels(
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
