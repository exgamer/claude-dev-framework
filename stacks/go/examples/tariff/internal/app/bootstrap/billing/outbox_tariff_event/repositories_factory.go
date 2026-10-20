package outbox_tariff_event

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq"
	"gorm.io/gorm"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	outboxpostgres "example.com/parking-service/internal/infrastructure/postgres/billing/outbox_tariff_event"
	outboxrabbit "example.com/parking-service/internal/infrastructure/rabbit/billing/outbox_tariff_event"
)

func newRepositoriesFactory(postgresClient *gorm.DB, publisher *rabbitmq.Publisher) *repositoriesFactory {
	return &repositoriesFactory{
		OutboxRepository:     outboxpostgres.NewPostgresRepository(postgresClient),
		OutboxEventPublisher: outboxrabbit.NewRabbitPublisher(publisher),
	}
}

type repositoriesFactory struct {
	OutboxRepository     outboxdomain.Repository
	OutboxEventPublisher outboxdomain.EventPublisher
}
