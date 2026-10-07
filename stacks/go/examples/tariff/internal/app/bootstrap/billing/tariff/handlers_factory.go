package tariff

import (
	tariffhttp "example.com/parking-service/internal/entrypoints/admin/http/billing/tariff"
)

func newHandlersFactory(servicesFactory *servicesFactory, workflowsFactory *workflowsFactory) *handlersFactory {
	return &handlersFactory{
		TariffHandler: tariffhttp.NewHandler(
			servicesFactory.TariffService,
			servicesFactory.SetDefaultTariffCommand,
			workflowsFactory.CreateTariffWorkflow,
		),
	}
}

type handlersFactory struct {
	TariffHandler *tariffhttp.Handler
}
