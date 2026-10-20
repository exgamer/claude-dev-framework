package tariff

import (
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/di"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	rabbitDI "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq"
	redisDI "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di"
	amqp "github.com/rabbitmq/amqp091-go"

	outboxbootstrap "example.com/parking-service/internal/app/bootstrap/billing/outbox_tariff_event"
	admindomain "example.com/parking-service/internal/domains/identity/admin"
	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
	tariffhttp "example.com/parking-service/internal/entrypoints/admin/http/billing/tariff"
	adminmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
	tariffrabbit "example.com/parking-service/internal/entrypoints/consumer/rabbit/billing/tariff"
	integrationtariffhttp "example.com/parking-service/internal/entrypoints/integration/http/billing/tariff"
	integrationmiddleware "example.com/parking-service/internal/entrypoints/integration/http/middleware"
	outboxrabbit "example.com/parking-service/internal/infrastructure/rabbit/billing/outbox_tariff_event"
)

// Module Модуль тарифов
type Module struct {
}

func (m *Module) Name() string {
	return "tariff"
}

func (m *Module) Init(a *app.App) error {
	postgresClient, err := postgresDI.GetDefaultPostgresConnection(a.Container)
	if err != nil {
		return err
	}

	redisClient, err := redisDI.GetRedisClient(a.Container)
	if err != nil {
		return err
	}

	adminService, err := di.Resolve[*admindomain.Service](a.Container)
	if err != nil {
		return err
	}

	partnerService, err := di.Resolve[*partnerdomain.Service](a.Container)
	if err != nil {
		return err
	}

	signatureSettings, err := di.Resolve[*integrationmiddleware.SignatureSettings](a.Container)
	if err != nil {
		return err
	}

	integrationsConfig, err := loadIntegrationsConfig()
	if err != nil {
		return err
	}

	outboxMaxAttempts, err := outboxbootstrap.LoadMaxAttempts()
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

	repositoriesFactory := newRepositoriesFactory(postgresClient, redisClient, publisher, integrationsConfig)
	servicesFactory := newServicesFactory(repositoriesFactory, outboxMaxAttempts)
	workflowsFactory := newWorkflowsFactory(repositoriesFactory, servicesFactory)
	handlersFactory := newHandlersFactory(servicesFactory, workflowsFactory)

	err = tariffhttp.SetRoutes(a, handlersFactory.TariffHandler, adminmiddleware.JWTAuthMiddleware(adminService))
	if err != nil {
		return err
	}

	err = integrationtariffhttp.SetRoutes(
		a,
		handlersFactory.IntegrationTariffHandler,
		integrationmiddleware.APIKeyMiddleware(partnerService),
		integrationmiddleware.SignatureAuthMiddleware(partnerService, *signatureSettings),
	)
	if err != nil {
		return err
	}

	consumerRetryConfig, err := loadConsumerRetryConfig()
	if err != nil {
		return err
	}

	if err := declareRetryQueue(a, consumerRetryConfig.retryDelay()); err != nil {
		return fmt.Errorf("tariff retry queue: %w", err)
	}

	consumersRegistry, err := rabbitDI.GetRabbitConsumersRegistry(a.Container)
	if err != nil {
		return err
	}

	consumersFactory := newConsumersFactory(servicesFactory, consumerRetryConfig)
	consumersRegistry.RegisterMultipleHandler(tariffrabbit.GetConsumers(consumersFactory.TariffConsumer))

	return nil
}

// declareRetryQueue Retry-очередь без читателя: NACK основной очереди уходит сюда, по TTL возвращается в основную. Соединение kernel-а, при старте
func declareRetryQueue(a *app.App, delay time.Duration) error {
	conn, err := rabbitDI.GetRabbitConnection(a.Container)
	if err != nil {
		return err
	}

	return rabbitmq.DeclareTopology(conn, func(ch *amqp.Channel) error {
		_, declareErr := ch.QueueDeclare(tariffrabbit.ParkingDeactivatedRetryQueue, true, false, false, false, retryQueueArguments(delay))

		return declareErr
	})
}

// retryQueueArguments TTL — задержка повтора; истёкшее сообщение — через exchange по умолчанию в основную очередь
func retryQueueArguments(delay time.Duration) amqp.Table {
	return amqp.Table{
		"x-message-ttl":             delay.Milliseconds(),
		"x-dead-letter-exchange":    "",
		"x-dead-letter-routing-key": tariffrabbit.ParkingDeactivatedQueue,
	}
}
