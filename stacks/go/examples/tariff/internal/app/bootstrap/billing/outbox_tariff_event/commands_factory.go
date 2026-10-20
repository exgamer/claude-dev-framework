package outbox_tariff_event

import (
	"github.com/spf13/cobra"

	outboxconsole "example.com/parking-service/internal/entrypoints/system/console/billing/outbox_tariff_event"
)

func newCommandsFactory(servicesFactory *servicesFactory) *commandsFactory {
	return &commandsFactory{
		RetryCommand: outboxconsole.NewRetryCommand(servicesFactory.RetryOutboxTariffEventsCommand),
	}
}

type commandsFactory struct {
	RetryCommand *cobra.Command
}
