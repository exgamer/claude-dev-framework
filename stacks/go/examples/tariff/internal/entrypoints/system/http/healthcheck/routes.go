package healthcheck

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
)

// SetRoutes Пробы без авторизации: их вызывает оркестратор, данных не отдают
func SetRoutes(a *app.App, handler *Handler) error {
	router, err := di.GetRouter(a.Container)
	if err != nil {
		return err
	}

	// Вне схемы /api/<context>/<version>/...: проба оркестратора, не ресурс API — путь задаёт манифест деплоя
	health := router.Group("/healthcheck")
	{
		health.Use(middleware.RequestInfoMiddleware(a))
		health.Use(middleware.LoggerMiddleware())
		health.Use(middleware.DebugMiddleware())
		health.Use(middleware.SentryMiddleware())
		health.Use(middleware.FormattedResponseMiddleware())
		health.Use(middleware.MetricsMiddleware(a))

		health.GET("/liveness", handler.Live())
		health.GET("/readiness", handler.Ready())
	}

	return nil
}
