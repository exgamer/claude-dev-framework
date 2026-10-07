package tariff

import (
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func newWorkflowsFactory(repositoriesFactory *repositoriesFactory) *workflowsFactory {
	return &workflowsFactory{
		CreateTariffWorkflow: tariffworkflow.NewCreateTariffWorkflow(
			repositoriesFactory.CreateTariffTxManager,
		),
	}
}

type workflowsFactory struct {
	CreateTariffWorkflow *tariffworkflow.CreateTariffWorkflow
}
