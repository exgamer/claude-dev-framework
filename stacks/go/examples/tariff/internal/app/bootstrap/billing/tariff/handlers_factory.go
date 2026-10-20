package tariff

import (
	tariffhttp "example.com/parking-service/internal/entrypoints/admin/http/billing/tariff"
	integrationtariffhttp "example.com/parking-service/internal/entrypoints/integration/http/billing/tariff"
)

func newHandlersFactory(servicesFactory *servicesFactory, workflowsFactory *workflowsFactory) *handlersFactory {
	return &handlersFactory{
		TariffHandler: tariffhttp.NewHandler(
			servicesFactory.TariffService,
			servicesFactory.GetDefaultTariffQuery,
			servicesFactory.SetDefaultTariffCommand,
			workflowsFactory.CreateTariffWorkflow,
		),
		IntegrationTariffHandler: integrationtariffhttp.NewHandler(servicesFactory.TariffService),
	}
}

type handlersFactory struct {
	TariffHandler            *tariffhttp.Handler
	IntegrationTariffHandler *integrationtariffhttp.Handler
}
