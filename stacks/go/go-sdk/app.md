# internal/app/app.go — инициализация приложения

Файл лежит в: `internal/app/app.go`

Содержит:
1. Объявление типа `App` с эмбедом `*app.App`
2. `RegisterAndInitKernels(...)` — подключение инфраструктурных ядер
3. `RegisterAndInitModules(...)` — регистрация бизнес-модулей
4. Проверки обязательных флагов окружения ядер на старте (fail-fast, `regulations/service-requirements.md`, «Конфиг») — между ядрами и модулями; без флага сервис не стартует. Как `requireRedisContextTimeout` эталона (`../examples/tariff/internal/app/app.go`: без `REDIS_CONTEXT_TIMEOUT_ENABLED=true` — ошибка).

Никакой бизнес-логики, никакой конфигурации — только сборка и стартовые проверки; конфигурация — в `config.go` модулей.

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

Эталон — `../examples/tariff/internal/app/app.go`.

```go
package app

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	httpapp "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/app"
	postgresapp "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app"
	rabbitapp "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/app"
	redisapp "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/app"
	sentryapp "git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app"

	"example.com/parking-service/internal/app/bootstrap/billing/tariff"
)

type App struct {
	*app.App
}

func NewApp() (*App, error) {
	appInstance := &App{
		App: app.NewApp(),
	}

	err := appInstance.RegisterAndInitKernels(
		&sentryapp.SentryKernel{},
		&postgresapp.PostgresKernel{},
		&httpapp.HttpKernel{},
		&redisapp.RedisKernel{},
		rabbitapp.NewRabbitKernel().EnableConsumer().EnablePublisher(),
	)
	if err != nil {
		return nil, err
	}

	err = appInstance.RegisterAndInitModules(
		&tariff.Module{},
	)
	if err != nil {
		return nil, err
	}

	return appInstance, nil
}
```
