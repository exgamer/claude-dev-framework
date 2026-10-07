package tariff

import (
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func newWorkflowsFactory(repositoriesFactory *repositoriesFactory, servicesFactory *servicesFactory) *workflowsFactory {
	return &workflowsFactory{
		CreateTariffWorkflow: tariffworkflow.NewCreateTariffWorkflow(
			repositoriesFactory.ParkingRepository,
			servicesFactory.TariffService,
		),
	}
}

type workflowsFactory struct {
	CreateTariffWorkflow *tariffworkflow.CreateTariffWorkflow
}
