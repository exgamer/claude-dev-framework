package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

func TestAPIKeyMiddleware(t *testing.T) {
	gin.SetMode(gin.TestMode)

	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.GET("/tariffs/1", APIKeyMiddleware(newPartnerService(false)), func(c *gin.Context) { c.Status(http.StatusOK) })

	cases := []struct {
		name, key string
		want      int
	}{
		{"no key", "", http.StatusUnauthorized},
		{"wrong key", "partner-ke", http.StatusUnauthorized},
		{"valid key", testAPIKey, http.StatusOK},
	}

	for _, tc := range cases {
		req := httptest.NewRequest(http.MethodGet, "/tariffs/1", nil)
		if tc.key != "" {
			req.Header.Set(APIKeyHeaderName, tc.key)
		}

		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
