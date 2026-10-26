package healthcheck

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	httpDI "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	rabbitDI "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/di"
	redisDI "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di"
	"github.com/ThreeDotsLabs/watermill-amqp/v2/pkg/amqp"
	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"

	healthcheckhttp "example.com/parking-service/internal/entrypoints/system/http/healthcheck"
)

// Module Пробы liveness/readiness; нужны PostgresKernel, RedisKernel, RabbitKernel и HttpKernel
type Module struct {
}

func (m *Module) Name() string {
	return "healthcheck"
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

	rabbitConnection, err := rabbitDI.GetRabbitConnection(a.Container)
	if err != nil {
		return err
	}

	router, err := httpDI.GetRouter(a.Container)
	if err != nil {
		return err
	}

	handler := healthcheckhttp.NewHandler(
		healthcheckhttp.Dependency{Name: "postgres", Probe: postgresProbe(postgresClient)},
		healthcheckhttp.Dependency{Name: "redis", Probe: redisProbe(redisClient)},
		healthcheckhttp.Dependency{Name: "rabbitmq", Probe: rabbitProbe(rabbitConnection)},
	)

	healthcheckhttp.SetRoutes(a, router, handler)

	return nil
}

func postgresProbe(db *gorm.DB) func(ctx context.Context) error {
	return func(ctx context.Context) error {
		sqlDB, err := db.DB()
		if err != nil {
			return err
		}

		return sqlDB.PingContext(ctx)
	}
}

func redisProbe(client *redis.Client) func(ctx context.Context) error {
	return func(ctx context.Context) error {
		return client.Ping(ctx).Err()
	}
}

// rabbitProbe Соединение переподключается само; пока оно не восстановлено, публикация и consumer-ы стоят
func rabbitProbe(connection *amqp.ConnectionWrapper) func(ctx context.Context) error {
	return func(_ context.Context) error {
		if !connection.IsConnected() {
			return errors.New("rabbitmq connection is not established")
		}

		return nil
	}
}
