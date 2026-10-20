package outbox_tariff_event

import (
	consoleapp "git.mpinnovations.kz/mps/go-packages/gosdk-console-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	rabbitDI "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/di"

	outboxrabbit "example.com/parking-service/internal/infrastructure/rabbit/billing/outbox_tariff_event"
)

// Module Повтор outbox-событий тарифов в консольном приложении; нужны PostgresKernel и RabbitKernel с EnablePublisher
type Module struct {
	// Console Ядро, в которое добавляется команда; до его Start
	Console *consoleapp.ConsoleKernel
}

func (m *Module) Name() string {
	return "outbox_tariff_event"
}

func (m *Module) Init(a *app.App) error {
	postgresClient, err := postgresDI.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	publishersRegistry, err := rabbitDI.GetRabbitPublishersRegistry(a.Container)
	if err != nil {
		return err
	}

	if err := publishersRegistry.Register(outboxrabbit.NewPublisherDefinition()); err != nil {
		return err
	}

	publisher, err := publishersRegistry.Get(outboxrabbit.PublisherName)
	if err != nil {
		return err
	}

	maxAttempts, err := LoadMaxAttempts()
	if err != nil {
		return err
	}

	retryConfig, err := loadRetryConfig()
	if err != nil {
		return err
	}

	repositoriesFactory := newRepositoriesFactory(postgresClient, publisher)
	servicesFactory := newServicesFactory(repositoriesFactory, retryConfig, maxAttempts)
	commandsFactory := newCommandsFactory(servicesFactory)

	m.Console.AddCommand(commandsFactory.RetryCommand)

	return nil
}
