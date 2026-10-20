package tariff

import (
	dbtransaction "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/transaction"
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq"
	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
	"example.com/parking-service/internal/infrastructure/http/cityparking/pricelimits"
	"example.com/parking-service/internal/infrastructure/http/partnerbilling/tariffs"
	outboxpostgres "example.com/parking-service/internal/infrastructure/postgres/billing/outbox_tariff_event"
	tariffpostgres "example.com/parking-service/internal/infrastructure/postgres/billing/tariff"
	parkingpostgres "example.com/parking-service/internal/infrastructure/postgres/handbook/parking"
	outboxrabbit "example.com/parking-service/internal/infrastructure/rabbit/billing/outbox_tariff_event"
	tariffredis "example.com/parking-service/internal/infrastructure/redis/billing/tariff"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func newRepositoriesFactory(
	postgresClient *gorm.DB,
	redisClient *redis.Client,
	publisher *rabbitmq.Publisher,
	integrationsConfig *integrationsConfig,
) *repositoriesFactory {
	return &repositoriesFactory{
		TariffRepository:      tariffpostgres.NewPostgresRepository(postgresClient),
		TariffCacheRepository: tariffredis.NewRedisRepository(redisClient),
		ParkingRepository:     parkingpostgres.NewPostgresRepository(postgresClient),
		OutboxRepository:      outboxpostgres.NewPostgresRepository(postgresClient),
		OutboxEventPublisher:  outboxrabbit.NewRabbitPublisher(publisher),
		PriceLimitRepository: pricelimits.NewHTTPRepository(
			integrationsConfig.CityParkingBaseURL,
			integrationsConfig.cityParkingTimeout(),
		),
		TariffNotifier: tariffs.NewHTTPNotifier(
			integrationsConfig.PartnerBillingBaseURL,
			integrationsConfig.partnerBillingTimeout(),
		),
		DefaultTariffTxManager: dbtransaction.NewManager[tariffdomain.Repository](
			postgresClient,
			func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
		),
		FailedAttemptTxManager: dbtransaction.NewManager[outboxdomain.Repository](
			postgresClient,
			func(tx *gorm.DB) outboxdomain.Repository { return outboxpostgres.NewPostgresRepository(tx) },
		),
		CreateTariffTxManager: dbtransaction.NewManager2[tariffdomain.Repository, outboxdomain.Repository](
			postgresClient,
			func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
			func(tx *gorm.DB) outboxdomain.Repository { return outboxpostgres.NewPostgresRepository(tx) },
		),
	}
}

type repositoriesFactory struct {
	TariffRepository       tariffdomain.Repository
	TariffCacheRepository  tariffdomain.CacheRepository
	ParkingRepository      parkingdomain.Repository
	OutboxRepository       outboxdomain.Repository
	OutboxEventPublisher   outboxdomain.EventPublisher
	PriceLimitRepository   tariffdomain.PriceLimitRepository
	TariffNotifier         tariffworkflow.TariffNotifier
	DefaultTariffTxManager tariffdomain.DefaultTariffTxManager
	FailedAttemptTxManager outboxdomain.FailedAttemptTxManager
	CreateTariffTxManager  tariffworkflow.CreateTariffTxManager
}
