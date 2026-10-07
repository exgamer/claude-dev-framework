package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/di"
	postgresDi "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	redisDi "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
	tariffhttp "example.com/parking-service/internal/entrypoints/admin/http/billing/tariff"
	httpmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
)

// Module Модуль тарифов
type Module struct {
}

func (m *Module) Name() string {
	return "tariff"
}

func (m *Module) Init(a *app.App) error {
	postgresClient, err := postgresDi.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	redisClient, err := redisDi.GetRedisClient(a.Container)
	if err != nil {
		return err
	}

	adminService, err := di.Resolve[*admindomain.Service](a.Container)
	if err != nil {
		return err
	}

	repositoriesFactory := newRepositoriesFactory(postgresClient, redisClient)
	servicesFactory := newServicesFactory(repositoriesFactory)
	workflowsFactory := newWorkflowsFactory(repositoriesFactory, servicesFactory)
	handlersFactory := newHandlersFactory(servicesFactory, workflowsFactory)

	return tariffhttp.SetRoutes(a, handlersFactory.TariffHandler, httpmiddleware.JwtAuth(adminService))
}
