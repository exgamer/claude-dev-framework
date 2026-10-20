package middleware

import (
	"errors"
	"net/http"
	"slices"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

// RequirePermissionMiddleware Пропускает администратора с правом permission, super-admin — без проверки; нет права — 403
func RequirePermissionMiddleware(permission string) gin.HandlerFunc {
	return func(c *gin.Context) {
		admin := admindomain.FromContext(c.Request.Context())
		// Ставится после JWTAuthMiddleware: без администратора в контексте — 500, маршрут собран неверно
		if admin == nil {
			response.ErrorResponse(c, errors.New("require permission: no admin in context, JWTAuthMiddleware is missing on the route"))

			return
		}

		isSuperAdmin := slices.Contains(admin.SystemRoles, admindomain.SuperAdminRole)
		if !isSuperAdmin && !slices.Contains(admin.Permissions, permission) {
			response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, errors.New("недостаточно прав"), nil)

			return
		}

		c.Next()
	}
}
