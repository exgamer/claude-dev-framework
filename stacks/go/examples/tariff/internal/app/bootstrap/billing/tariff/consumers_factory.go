package tariff

import (
	tariffrabbit "example.com/parking-service/internal/entrypoints/consumer/rabbit/billing/tariff"
)

func newConsumersFactory(servicesFactory *servicesFactory, retryConfig *consumerRetryConfig) *consumersFactory {
	return &consumersFactory{
		TariffConsumer: tariffrabbit.NewConsumer(servicesFactory.DeactivateParkingTariffsCommand, retryConfig.MaxAttempts),
	}
}

type consumersFactory struct {
	TariffConsumer *tariffrabbit.Consumer
}
