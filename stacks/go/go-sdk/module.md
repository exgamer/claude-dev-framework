# internal/app/bootstrap — бизнес-модуль

Файл лежит в: `internal/app/bootstrap/{domain}/{module}/module.go`

Структура пути: `{domain}/{module}` (например `billing/tariff`) или просто `{module}` если домен не нужен.

Рядом с `module.go` лежат фабрики и конфиг (состав — `../structure.md`, «Bootstrap / Application Layer»):
- `config.go` (если модулю нужны настройки из env — внешние системы, лимиты, таймауты)
- `repositories_factory.go`
- `services_factory.go` (сервисы, Command и Query домена)
- `workflows_factory.go` (если есть workflow — только сборка workflow, не в `services_factory.go`)
- `handlers_factory.go`
- `consumers_factory.go` (если есть очередь)

Модуль консоли — не здесь: `internal/console/bootstrap/{domain}/{module}/` со своим `module.go` (поле `Console`) и `commands_factory.go` (`../structure.md`, «Entrypoint Layer»).

Эталон — `../examples/tariff/internal/app/bootstrap/billing/tariff/` (`config.go`, `workflows_factory.go`); консольный — `../examples/tariff/internal/console/bootstrap/billing/outbox_tariff_event/` (`commands_factory.go`).

## Module.Init — порядок сборки

```
client (postgres DI), config (load{Name}Config)
  → repositoriesFactory
    → servicesFactory
      → workflowsFactory (repositoriesFactory, servicesFactory)
        → handlersFactory (servicesFactory, workflowsFactory) → router (http DI) → SetRoutes(a, router, handler, auth)
      → consumersFactory → GetConsumers(...) → reg.RegisterMultipleHandler(...)
      → commandsFactory → m.Console.AddCommand(...)   (только модуль консоли, internal/console/bootstrap/)
```

## Правила

- Модули не зависят друг от друга, кроме двух форм: готовый объект через DI (владелец регистрирует, потребитель — `di.Resolve` в `Init`, владелец раньше в `app.go`) и `Load{Name}()` настройки владельца — `../structure.md`, «Зависимости модулей bootstrap»
- `Name()` возвращает строку-идентификатор модуля (уникальная в рамках приложения)
- Если нет Rabbit — блок с consumers не нужен
- DI использовать только внутри `Init` — не передавать `a.Container` в фабрики, сервисы и `SetRoutes` (`../structure.md`, «Bootstrap / Application Layer»)
- Маршруты регистрируются только через `SetRoutes` в `module.go`: роутер, его настройка и auth-middleware — в `Init`, в `SetRoutes` — аргументами (`../structure.md`, «Bootstrap / Application Layer», «Маршруты модуля»)
- HTTP server не создавать вручную — только через `HttpKernel`

## Шаблон

```go
package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	httpDI "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"

	tariffhttp "example.com/parking-service/internal/entrypoints/admin/http/billing/tariff"
)

type Module struct{}

func (m *Module) Name() string {
	return "tariff"
}

func (m *Module) Init(a *app.App) error {
	client, err := postgresDI.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	integrationsConfig, err := loadIntegrationsConfig()
	if err != nil {
		return err
	}

	repositoriesFactory := newRepositoriesFactory(client, integrationsConfig)
	servicesFactory := newServicesFactory(repositoriesFactory)
	workflowsFactory := newWorkflowsFactory(repositoriesFactory, servicesFactory)
	handlersFactory := newHandlersFactory(servicesFactory, workflowsFactory)

	router, err := httpDI.GetRouter(a.Container)
	if err != nil {
		return err
	}

	tariffhttp.SetRoutes(a, router, handlersFactory.TariffHandler, adminAuth)

	return nil
}
```

`adminAuth` — auth-middleware контекста, собранная в `Init` (в эталоне — `adminmiddleware.JWTAuthMiddleware(adminService)`, сервис — `di.Resolve`).

## Шаблон с Rabbit consumers

```go
func (m *Module) Init(a *app.App) error {
	client, err := postgresDI.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	repositoriesFactory := newRepositoriesFactory(client)
	servicesFactory := newServicesFactory(repositoriesFactory)
	handlersFactory := newHandlersFactory(servicesFactory)

	router, err := httpDI.GetRouter(a.Container)
	if err != nil {
		return err
	}

	cityhttp.SetRoutes(a, router, handlersFactory.CityHandler, adminAuth)

	consumersFactory := newConsumersFactory(servicesFactory)
	consumers := cityconsumer.GetConsumers(consumersFactory.CityConsumer)

	reg, err := rabbitDI.GetRabbitConsumersRegistry(a.Container)
	if err != nil {
		return err
	}

	reg.RegisterMultipleHandler(consumers)

	return nil
}
```
