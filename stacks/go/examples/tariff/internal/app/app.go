package app

import (
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	httpapp "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/app"
	httpdi "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	postgresapp "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app"
	rabbitapp "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/app"
	redisapp "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/app"
	redisdi "git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di"
	sentryapp "git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app"
	"github.com/redis/go-redis/v9"

	"example.com/parking-service/internal/app/bootstrap/billing/tariff"
	"example.com/parking-service/internal/app/bootstrap/identity/auth"
	"example.com/parking-service/internal/app/bootstrap/system/healthcheck"
)

// App HTTP-сервис
type App struct {
	*app.App
}

func NewApp() (*App, error) {
	appInstance := &App{
		App: app.NewApp(),
	}

	err := appInstance.RegisterAndInitKernels(
		&sentryapp.SentryKernel{},
		&postgresapp.PostgresKernel{},
		&httpapp.HttpKernel{},
		&redisapp.RedisKernel{},
		rabbitapp.NewRabbitKernel().EnableConsumer().EnablePublisher(),
	)
	if err != nil {
		return nil, err
	}

	if err := registerCors(appInstance.App); err != nil {
		return nil, err
	}

	redisClient, err := redisdi.GetRedisClient(appInstance.Container)
	if err != nil {
		return nil, err
	}

	if err := requireRedisContextTimeout(redisClient); err != nil {
		return nil, err
	}

	err = appInstance.RegisterAndInitModules(
		&healthcheck.Module{},
		&auth.Module{},
		&tariff.Module{},
	)
	if err != nil {
		return nil, err
	}

	return appInstance, nil
}

// registerCors CORS на весь роутер до регистрации маршрутов; разрешённые источники — CORS_ALLOWED_ORIGINS
func registerCors(a *app.App) error {
	router, err := httpdi.GetRouter(a.Container)
	if err != nil {
		return err
	}

	router.Use(middleware.CorsMiddleware(a))

	return nil
}

// requireRedisContextTimeout Без REDIS_CONTEXT_TIMEOUT_ENABLED=true таймауты Redis-репозиториев не действуют — сервис не стартует
func requireRedisContextTimeout(client *redis.Client) error {
	if !client.Options().ContextTimeoutEnabled {
		return errors.New("REDIS_CONTEXT_TIMEOUT_ENABLED must be true: redis calls ignore context deadlines otherwise")
	}

	return nil
}
