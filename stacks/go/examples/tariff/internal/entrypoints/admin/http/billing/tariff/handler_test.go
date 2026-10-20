package tariff

import (
	"net/http"
	"net/http/httptest"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	admindomain "example.com/parking-service/internal/domains/identity/admin"
	adminmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

// withAdmin Кладёт администратора в контекст, как JWTAuthMiddleware
func withAdmin(admin *admindomain.Admin) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Request = c.Request.WithContext(admindomain.WithAdmin(c.Request.Context(), admin))

		c.Next()
	}
}

// TestHandler_View_ParkingScope Цепочка middleware → хендлер → сервис → репозиторий: свой тариф — 200, чужой — 404,
// в том числе из кеша; super-admin видит любой; маршрут без ResolveAdminParkingsMiddleware — 500, а не «без ограничения»
func TestHandler_View_ParkingScope(t *testing.T) {
	gin.SetMode(gin.TestMode)

	parkingAdmin := &admindomain.Admin{ID: 1, AllowedParkingIDs: []uint{7}}
	superAdmin := &admindomain.Admin{ID: 2, SystemRoles: []string{admindomain.SuperAdminRole}}

	cases := []struct {
		name         string
		admin        *admindomain.Admin
		target       string
		cached       bool
		withoutScope bool
		want         int
	}{
		{"own tariff", parkingAdmin, "/tariffs/1", false, false, http.StatusOK},
		{"other parking tariff", parkingAdmin, "/tariffs/2", false, false, http.StatusNotFound},
		{"other parking tariff from cache", parkingAdmin, "/tariffs/2", true, false, http.StatusNotFound},
		{"super-admin sees any", superAdmin, "/tariffs/2", false, false, http.StatusOK},
		{"route without ResolveAdminParkingsMiddleware", superAdmin, "/tariffs/1", false, true, http.StatusInternalServerError},
	}

	for _, tc := range cases {
		tariffs := billingfakes.NewTariffRepository(
			&tariffdomain.Tariff{ID: 1, ParkingID: 7},
			&tariffdomain.Tariff{ID: 2, ParkingID: 8},
		)
		cache := billingfakes.NewTariffCacheRepository()

		if tc.cached {
			cache.Items[2] = *tariffs.Items[2]
		}

		handler := NewHandler(tariffdomain.NewService(tariffs, cache), nil, nil)

		chain := []gin.HandlerFunc{withAdmin(tc.admin)}
		if !tc.withoutScope {
			chain = append(chain, adminmiddleware.ResolveAdminParkingsMiddleware())
		}

		r := gin.New()
		r.Use(gosdkmiddleware.FormattedResponseMiddleware())
		r.GET("/tariffs/:id", append(chain, handler.View())...)

		w := httptest.NewRecorder()
		r.ServeHTTP(w, httptest.NewRequest(http.MethodGet, tc.target, nil))

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
