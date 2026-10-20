package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

const testPermission = "parking.admin.tariffs.update"

// withAdmin Кладёт администратора в контекст, как JWTAuthMiddleware
func withAdmin(admin *admindomain.Admin) gin.HandlerFunc {
	return func(c *gin.Context) {
		if admin != nil {
			ctx := admindomain.WithAdmin(c.Request.Context(), admin)
			c.Request = c.Request.WithContext(ctx)
		}

		c.Next()
	}
}

func okHandler(c *gin.Context) {
	c.Status(http.StatusOK)
}

func TestRequirePermissionMiddleware(t *testing.T) {
	gin.SetMode(gin.TestMode)

	cases := []struct {
		name  string
		admin *admindomain.Admin
		want  int
	}{
		{"has permission", &admindomain.Admin{ID: 1, Permissions: []string{testPermission}}, http.StatusOK},
		{"other permission", &admindomain.Admin{ID: 2, Permissions: []string{"parking.admin.tariffs.view"}}, http.StatusForbidden},
		{"super-admin without permissions", &admindomain.Admin{ID: 3, SystemRoles: []string{admindomain.SuperAdminRole}}, http.StatusOK},
		{"no admin in context", nil, http.StatusInternalServerError},
	}

	for _, tc := range cases {
		r := gin.New()
		r.Use(gosdkmiddleware.FormattedResponseMiddleware())
		r.GET("/tariffs", withAdmin(tc.admin), RequirePermissionMiddleware(testPermission), okHandler)

		req := httptest.NewRequest(http.MethodGet, "/tariffs", nil)
		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
