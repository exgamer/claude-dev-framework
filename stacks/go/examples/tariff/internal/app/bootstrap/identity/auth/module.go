package auth

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/di"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	redisDI "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di"
)

// Module Аутентификация администратора и партнёра; регистрируется до модулей, которые берут её сервисы из DI
type Module struct {
}

func (m *Module) Name() string {
	return "auth"
}

func (m *Module) Init(a *app.App) error {
	postgresClient, err := postgresDI.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	redisClient, err := redisDI.GetRedisClient(a.Container)
	if err != nil {
		return err
	}

	adminConfig, err := loadAdminAuthConfig()
	if err != nil {
		return err
	}

	partnerConfig, err := loadPartnerAuthConfig()
	if err != nil {
		return err
	}

	repositoriesFactory, err := newRepositoriesFactory(postgresClient, redisClient, adminConfig)
	if err != nil {
		return err
	}

	servicesFactory := newServicesFactory(repositoriesFactory, adminConfig.settings(), partnerConfig.settings())

	di.Register(a.Container, servicesFactory.AdminService)
	di.Register(a.Container, servicesFactory.PartnerService)
	di.Register(a.Container, partnerConfig.signatureSettings())

	return nil
}
