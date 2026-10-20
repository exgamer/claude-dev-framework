package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"

	adminmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
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

		// /api/<context>/<version>/<domain>/<entity>: модуль совпадает с сущностью — не повторяется
		billing := service.Group("/admin/v1/billing")
		{
			billing.Use(middleware.FormattedResponseMiddleware())
			billing.Use(middleware.MetricsMiddleware(a))
			// Отказы auth-middleware оборачивает FormattedResponseMiddleware — они ставятся после неё
			billing.Use(adminAuth)
			billing.Use(adminmiddleware.ResolveAdminParkingsMiddleware())

			billing.GET("/tariffs", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.view"), handler.Index())
			billing.GET("/tariffs/default", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.view"), handler.Default())
			billing.GET("/tariffs/:id", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.view"), handler.View())
			billing.POST("/tariffs", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.create"), adminmiddleware.RequireJSONMiddleware(), handler.Create())
			billing.PATCH("/tariffs/:id", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.update"), adminmiddleware.RequireJSONMiddleware(), handler.Update())
			billing.POST("/tariffs/:id/set-default", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.update"), handler.SetDefault())
		}
	}

	return nil
}
