package middleware

import (
	"context"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

type stubVerifier struct {
	byToken map[string]uint
}

func (s stubVerifier) Verify(raw string) (*admindomain.TokenClaims, error) {
	id, ok := s.byToken[raw]
	if !ok {
		return nil, errors.New("bad token")
	}

	return &admindomain.TokenClaims{AdminID: id, GuardName: "admin-api"}, nil
}

type stubRepository struct {
	admins map[uint]*admindomain.Admin
}

func (s stubRepository) GetByID(_ context.Context, id uint) (*admindomain.Admin, error) {
	if id == brokenAdminID {
		return nil, errors.New("db down")
	}

	return s.admins[id], nil
}

func (s stubRepository) GetPermissionNames(_ context.Context, id uint) ([]string, error) {
	return s.admins[id].Permissions, nil
}

func (s stubRepository) GetAllowedParkingIDs(_ context.Context, id uint) ([]uint, error) {
	return s.admins[id].AllowedParkingIDs, nil
}

type noCacheRepository struct{}

func (noCacheRepository) Get(context.Context, uint) (*admindomain.Admin, error) {
	return nil, nil
}

func (noCacheRepository) Set(context.Context, *admindomain.Admin, time.Duration) error {
	return nil
}

const brokenAdminID = 3

func newRouter(t *testing.T) *gin.Engine {
	t.Helper()
	gin.SetMode(gin.TestMode)

	service := admindomain.NewService(
		stubVerifier{byToken: map[string]uint{"operator": 1, "revoked": 2, "broken": brokenAdminID}},
		stubRepository{admins: map[uint]*admindomain.Admin{
			1: {ID: 1, Permissions: []string{"parking.admin.sessions.view"}},
			2: {ID: 2, RevokedAt: &time.Time{}},
		}},
		noCacheRepository{},
		admindomain.AuthSettings{GuardName: "admin-api", CacheTTL: time.Minute},
	)

	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.GET("/me", JWTAuthMiddleware(service), func(c *gin.Context) {
		if admindomain.FromContext(c.Request.Context()) == nil {
			c.Status(http.StatusTeapot)

			return
		}

		c.Status(http.StatusOK)
	})

	return r
}

func TestJWTAuthMiddleware(t *testing.T) {
	r := newRouter(t)

	cases := []struct {
		name, auth string
		want       int
	}{
		{"no header", "", http.StatusUnauthorized},
		{"not bearer", "Basic abc", http.StatusUnauthorized},
		{"empty token", "Bearer  ", http.StatusUnauthorized},
		{"bad token", "Bearer nope", http.StatusUnauthorized},
		{"revoked admin", "Bearer revoked", http.StatusUnauthorized},
		{"storage error", "Bearer broken", http.StatusInternalServerError},
		{"admin in context", "Bearer operator", http.StatusOK},
		{"lowercase bearer scheme", "bearer operator", http.StatusOK},
	}

	for _, tc := range cases {
		req := httptest.NewRequest(http.MethodGet, "/me", nil)
		if tc.auth != "" {
			req.Header.Set("Authorization", tc.auth)
		}

		w := httptest.NewRecorder()
		r.ServeHTTP(w, req)

		if w.Code != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, w.Code, tc.want)
		}
	}
}
