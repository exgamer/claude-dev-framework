package healthcheck

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
)

func available(context.Context) error {
	return nil
}

func unavailable(context.Context) error {
	return errors.New("connection refused")
}

// hanging Зависшая зависимость: отвечает только по истечении контекста пробы
func hanging(ctx context.Context) error {
	<-ctx.Done()

	return ctx.Err()
}

// TestHandler_Probes Liveness не смотрит на зависимости; readiness — 200, только если доступны все, зависшая — 503 по таймауту
func TestHandler_Probes(t *testing.T) {
	gin.SetMode(gin.TestMode)

	cases := []struct {
		name   string
		target string
		probes []func(context.Context) error
		want   int
	}{
		{"liveness with unavailable dependency", "/liveness", []func(context.Context) error{unavailable}, http.StatusOK},
		{"readiness all available", "/readiness", []func(context.Context) error{available, available}, http.StatusOK},
		{"readiness one unavailable", "/readiness", []func(context.Context) error{available, unavailable}, http.StatusServiceUnavailable},
		{"readiness hanging dependency", "/readiness", []func(context.Context) error{hanging}, http.StatusServiceUnavailable},
	}

	for _, tc := range cases {
		dependencies := make([]Dependency, 0, len(tc.probes))
		for _, p := range tc.probes {
			dependencies = append(dependencies, Dependency{Name: "dependency", Probe: p})
		}

		handler := NewHandler(dependencies...)

		r := gin.New()
		r.Use(gosdkmiddleware.FormattedResponseMiddleware())
		r.GET("/liveness", handler.Live())
		r.GET("/readiness", handler.Ready())

		w := httptest.NewRecorder()
		r.ServeHTTP(w, httptest.NewRequest(http.MethodGet, tc.target, nil))

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
