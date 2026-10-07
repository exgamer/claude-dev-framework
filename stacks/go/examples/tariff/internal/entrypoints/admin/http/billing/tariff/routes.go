package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

func SetRoutes(a *app.App, handler *Handler, adminAuth gin.HandlerFunc) error {
	router, err := di.GetRouter(a.Container)
	if err != nil {
		return err
	}

	service := router.Group("/api")
	{
		service.Use(middleware.RequestInfoMiddleware(a))
		service.Use(middleware.LoggerMiddleware())
		service.Use(middleware.DebugMiddleware())
		service.Use(middleware.SentryMiddleware())

		v1 := service.Group("/v1/admin")
		{
			v1.Use(middleware.FormattedResponseMiddleware())
			v1.Use(middleware.MetricsMiddleware(a))
			v1.Use(adminAuth)

			v1.GET("/tariffs", handler.Index())
			v1.GET("/tariffs/:id", handler.View())
			v1.POST("/tariffs", handler.Create())
			v1.PATCH("/tariffs/:id", handler.Update())
			v1.POST("/tariffs/:id/set-default", handler.SetDefault())
		}
	}

	return nil
}
