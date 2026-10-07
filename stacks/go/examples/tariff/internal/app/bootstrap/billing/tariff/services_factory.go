package tariff

import (
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func newServicesFactory(repositoriesFactory *repositoriesFactory) *servicesFactory {
	return &servicesFactory{
		TariffService: tariffdomain.NewService(
			repositoriesFactory.TariffRepository,
			repositoriesFactory.TariffCacheRepository,
		),
		SetDefaultTariffCommand: tariffdomain.NewSetDefaultTariffCommand(
			repositoriesFactory.TariffRepository,
			repositoriesFactory.TariffCacheRepository,
			repositoriesFactory.DefaultTariffTxManager,
		),
	}
}

type servicesFactory struct {
	TariffService           *tariffdomain.Service
	SetDefaultTariffCommand *tariffdomain.SetDefaultTariffCommand
}
