package tariff

import (
	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func newServicesFactory(repositoriesFactory *repositoriesFactory, outboxMaxAttempts uint) *servicesFactory {
	return &servicesFactory{
		TariffService: tariffdomain.NewService(
			repositoriesFactory.TariffRepository,
			repositoriesFactory.TariffCacheRepository,
		),
		GetDefaultTariffQuery: tariffdomain.NewGetDefaultTariffQuery(
			repositoriesFactory.TariffRepository,
			repositoriesFactory.TariffCacheRepository,
		),
		SetDefaultTariffCommand: tariffdomain.NewSetDefaultTariffCommand(
			repositoriesFactory.TariffCacheRepository,
			repositoriesFactory.DefaultTariffTxManager,
		),
		PublishOutboxTariffEventCommand: outboxdomain.NewPublishOutboxTariffEventCommand(
			repositoriesFactory.OutboxRepository,
			repositoriesFactory.OutboxEventPublisher,
			repositoriesFactory.FailedAttemptTxManager,
			outboxMaxAttempts,
		),
	}
}

type servicesFactory struct {
	TariffService                   *tariffdomain.Service
	GetDefaultTariffQuery           *tariffdomain.GetDefaultTariffQuery
	SetDefaultTariffCommand         *tariffdomain.SetDefaultTariffCommand
	PublishOutboxTariffEventCommand *outboxdomain.PublishOutboxTariffEventCommand
}
