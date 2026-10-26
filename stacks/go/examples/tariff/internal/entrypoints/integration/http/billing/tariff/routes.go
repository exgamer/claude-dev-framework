package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

// AuthMiddlewares Проверки машинного вызова партнёра; порядок в группе — ключ API, затем подпись
type AuthMiddlewares struct {
	// APIKey Дешёвый отказ до чтения тела и Redis
	APIKey    gin.HandlerFunc
	Signature gin.HandlerFunc
}

// SetRoutes Машинный вызов партнёра: сначала ключ API, затем подпись
func SetRoutes(a *app.App, router *gin.Engine, handler *Handler, auth AuthMiddlewares) {
	service := router.Group("/api")
	{
		// LoggerMiddleware нет: на DEBUG он пишет заголовки и не маскирует Api-Key
		service.Use(middleware.RequestInfoMiddleware(a))
		service.Use(middleware.DebugMiddleware())
		service.Use(middleware.SentryMiddleware())

		// /api/<context>/<version>/<domain>/<entity>
		billing := service.Group("/integration/v1/billing")
		{
			billing.Use(middleware.FormattedResponseMiddleware())
			billing.Use(middleware.MetricsMiddleware(a))
			// Отказы auth-middleware оборачивает FormattedResponseMiddleware — они ставятся после неё
			billing.Use(auth.APIKey)
			billing.Use(auth.Signature)

			billing.GET("/tariffs/:id", handler.View())
		}
	}
}
