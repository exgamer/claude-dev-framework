package outbox_tariff_event

import (
	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

func newServicesFactory(repositoriesFactory *repositoriesFactory, retryConfig *retryConfig, maxAttempts uint) *servicesFactory {
	publishCommand := outboxdomain.NewPublishOutboxTariffEventCommand(
		repositoriesFactory.OutboxRepository,
		repositoriesFactory.OutboxEventPublisher,
		repositoriesFactory.FailedAttemptTxManager,
		maxAttempts,
	)

	return &servicesFactory{
		RetryOutboxTariffEventsCommand: outboxdomain.NewRetryOutboxTariffEventsCommand(
			repositoriesFactory.OutboxRepository,
			publishCommand,
			retryConfig.settings(),
		),
	}
}

type servicesFactory struct {
	RetryOutboxTariffEventsCommand *outboxdomain.RetryOutboxTariffEventsCommand
}
