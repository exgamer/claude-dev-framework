package healthcheck

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

// SetRoutes Пробы без авторизации: их вызывает оркестратор, данных не отдают
func SetRoutes(a *app.App, router *gin.Engine, handler *Handler) {
	// Путь пробы задаёт манифест деплоя оркестратора, он вне схемы /api/<context>/<version>/...
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
}
