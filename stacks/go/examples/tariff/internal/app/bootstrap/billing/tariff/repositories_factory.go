package tariff

import (
	dbtransaction "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/transaction"
	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
	tariffpostgres "example.com/parking-service/internal/infrastructure/postgres/billing/tariff"
	parkingpostgres "example.com/parking-service/internal/infrastructure/postgres/handbook/parking"
	tariffredis "example.com/parking-service/internal/infrastructure/redis/billing/tariff"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func newRepositoriesFactory(postgresClient *gorm.DB, redisClient *redis.Client) *repositoriesFactory {
	return &repositoriesFactory{
		TariffRepository:      tariffpostgres.NewPostgresRepository(postgresClient),
		TariffCacheRepository: tariffredis.NewRedisRepository(redisClient),
		ParkingRepository:     parkingpostgres.NewPostgresRepository(postgresClient),
		DefaultTariffTxManager: dbtransaction.NewManager[tariffdomain.Repository](
			postgresClient,
			func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
		),
		CreateTariffTxManager: dbtransaction.NewManager2[parkingdomain.Repository, tariffdomain.Repository](
			postgresClient,
			func(tx *gorm.DB) parkingdomain.Repository { return parkingpostgres.NewPostgresRepository(tx) },
			func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
		),
	}
}

type repositoriesFactory struct {
	TariffRepository       tariffdomain.Repository
	TariffCacheRepository  tariffdomain.CacheRepository
	ParkingRepository      parkingdomain.Repository
	DefaultTariffTxManager tariffdomain.DefaultTariffTxManager
	CreateTariffTxManager  tariffworkflow.CreateTariffTxManager
}
