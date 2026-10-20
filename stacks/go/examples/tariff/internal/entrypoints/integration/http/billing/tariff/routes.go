package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

// AuthMiddlewares Проверки машинного вызова партнёра; порядок в группе — ключ API, затем подпись
type AuthMiddlewares struct {
	// APIKey Дешёвый отказ до чтения тела и Redis
	APIKey    gin.HandlerFunc
	Signature gin.HandlerFunc
}

// SetRoutes Машинный вызов партнёра: сначала ключ API, затем подпись.
// LoggerMiddleware в группе нет: на DEBUG он пишет заголовки, а маскирует только Authorization и Cookie — Api-Key попал бы в лог
func SetRoutes(a *app.App, handler *Handler, auth AuthMiddlewares) error {
	router, err := di.GetRouter(a.Container)
	if err != nil {
		return err
	}

	service := router.Group("/api")
	{
		service.Use(middleware.RequestInfoMiddleware(a))
		service.Use(middleware.DebugMiddleware())
		service.Use(middleware.SentryMiddleware())

		v1 := service.Group("/v1/integration")
		{
			v1.Use(middleware.FormattedResponseMiddleware())
			v1.Use(middleware.MetricsMiddleware(a))
			v1.Use(auth.APIKey)
			v1.Use(auth.Signature)

			v1.GET("/tariffs/:id", handler.View())
		}
	}

	return nil
}
