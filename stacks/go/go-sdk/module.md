# internal/app/bootstrap — бизнес-модуль

Файл лежит в: `internal/app/bootstrap/{domain}/{module}/module.go`

Структура пути: `{domain}/{module}` (например `billing/tariff`) или просто `{module}` если домен не нужен.

Рядом с `module.go` лежат фабрики:
- `repositories_factory.go`
- `services_factory.go`
- `handlers_factory.go`
- `consumers_factory.go` (если есть очередь)

## Module.Init — порядок сборки

```
client (postgres DI)
  → repositoryFactory
    → servicesFactory
      → handlersFactory → SetRoutes(a, handler)
      → consumersFactory → GetConsumers(...) → reg.RegisterMultipleHandler(...)
```

## Правила

- Модули не зависят друг от друга
- `Name()` возвращает строку-идентификатор модуля (уникальная в рамках приложения)
- Если нет Rabbit — блок с consumers не нужен
- DI использовать только внутри `Init` — не передавать `a.Container` в фабрики и сервисы
- Маршруты регистрируются только через `SetRoutes` в `module.go`
- HTTP server не создавать вручную — только через `HttpKernel`

## Шаблон

```go
package tariff

import (
    tariffhttp "git.example.com/.../internal/entrypoints/client/http/billing/tariff"
    "github.com/exgamer/gosdk-core/pkg/app"
    postgresDi "github.com/exgamer/gosdk-postgres-core/pkg/di"
)

type Module struct{}

func (m *Module) Name() string {
    return "tariff"
}

func (m *Module) Init(a *app.App) error {
    client, err := postgresDi.GetDefaultPostgresConnection(a.Container)
    if err != nil {
        return err
    }

    repositoryFactory := newRepositoriesFactory(client)
    servicesFactory := newServicesFactory(repositoryFactory)
    handlersFactory := newHandlersFactory(servicesFactory)

    return tariffhttp.SetRoutes(a, handlersFactory.TariffHandler)
}
```

## Шаблон с Rabbit consumers

```go
func (m *Module) Init(a *app.App) error {
    client, err := postgresDi.GetDefaultPostgresConnection(a.Container)
    if err != nil {
        return err
    }

    repositoryFactory := newRepositoriesFactory(client)
    servicesFactory := newServicesFactory(repositoryFactory)
    handlersFactory := newHandlersFactory(servicesFactory)

    err = cityhttp.SetRoutes(a, handlersFactory.CityHandler)
    if err != nil {
        return err
    }

    consumersFactory := newConsumersFactory()
    consumers := cityconsumer.GetConsumers(consumersFactory.CityConsumer)

    reg, err := rabbitDi.GetRabbitConsumersRegistry(a.Container)
    if err != nil {
        return err
    }

    reg.RegisterMultipleHandler(consumers)

    return nil
}
```
