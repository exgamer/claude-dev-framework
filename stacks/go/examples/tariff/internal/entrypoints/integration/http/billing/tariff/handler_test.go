package tariff

import (
	"net/http"
	"net/http/httptest"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

// TestHandler_View Партнёр получает тариф любой парковки; несуществующий — 404, нечисловой id — 400
func TestHandler_View(t *testing.T) {
	gin.SetMode(gin.TestMode)

	tariffs := billingfakes.NewTariffRepository(
		&tariffdomain.Tariff{ID: 1, ParkingID: 7},
		&tariffdomain.Tariff{ID: 2, ParkingID: 8},
	)
	handler := NewHandler(tariffdomain.NewService(tariffs, billingfakes.NewTariffCacheRepository()))

	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.GET("/tariffs/:id", handler.View())

	cases := []struct {
		name, target string
		want         int
	}{
		{"any parking", "/tariffs/2", http.StatusOK},
		{"not found", "/tariffs/3", http.StatusNotFound},
		{"id not a number", "/tariffs/abc", http.StatusUnprocessableEntity},
		{"zero id", "/tariffs/0", http.StatusUnprocessableEntity},
		{"negative id", "/tariffs/-1", http.StatusUnprocessableEntity},
	}

	for _, tc := range cases {
		w := httptest.NewRecorder()
		r.ServeHTTP(w, httptest.NewRequest(http.MethodGet, tc.target, nil))

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
