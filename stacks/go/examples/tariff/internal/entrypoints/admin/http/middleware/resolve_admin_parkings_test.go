package middleware

import (
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

var (
	parkingAdmin = &admindomain.Admin{ID: 1, AllowedParkingIDs: []uint{7}}
	noParkings   = &admindomain.Admin{ID: 2}
	superAdmin   = &admindomain.Admin{ID: 3, SystemRoles: []string{admindomain.SuperAdminRole}}
)

// scopeRecorder Запоминает скоуп из контекста и тело, которое дошло до хендлера
type scopeRecorder struct {
	scope  tariffdomain.ParkingScope
	body   string
	called bool
}

func (s *scopeRecorder) handler(c *gin.Context) {
	scope, err := ParkingScopeFromContext(c.Request.Context())
	if err != nil {
		c.Status(http.StatusInternalServerError)

		return
	}

	body, _ := io.ReadAll(c.Request.Body)
	s.scope, s.body, s.called = scope, string(body), true
	c.Status(http.StatusOK)
}

func serveResolve(admin *admindomain.Admin, method, target, body string) (*httptest.ResponseRecorder, *scopeRecorder) {
	gin.SetMode(gin.TestMode)

	recorder := &scopeRecorder{}
	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.Handle(method, "/tariffs", withAdmin(admin), ResolveAdminParkingsMiddleware(), recorder.handler)

	var reader io.Reader
	if body != "" {
		reader = strings.NewReader(body)
	}

	req := httptest.NewRequest(method, target, reader)
	if body != "" {
		req.Header.Set("Content-Type", "application/json")
	}

	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	return w, recorder
}

func TestResolveAdminParkingsMiddleware_PutsScopeIntoContext(t *testing.T) {
	w, recorder := serveResolve(parkingAdmin, http.MethodGet, "/tariffs", "")

	require.Equal(t, http.StatusOK, w.Code)
	assert.Equal(t, tariffdomain.ParkingScope{IDs: []uint{7}}, recorder.scope)
}

func TestResolveAdminParkingsMiddleware_SuperAdminIsUnrestricted(t *testing.T) {
	w, recorder := serveResolve(superAdmin, http.MethodGet, "/tariffs?parking_id=99", "")

	require.Equal(t, http.StatusOK, w.Code)
	assert.Equal(t, tariffdomain.ParkingScope{All: true}, recorder.scope)
}

func TestResolveAdminParkingsMiddleware_AdminWithoutParkingsSeesNothing(t *testing.T) {
	w, recorder := serveResolve(noParkings, http.MethodGet, "/tariffs", "")

	require.Equal(t, http.StatusOK, w.Code)
	assert.False(t, recorder.scope.All, "пустой скоуп — не «без ограничения»")
	assert.Empty(t, recorder.scope.IDs)
}

func TestResolveAdminParkingsMiddleware_ForbidsOtherParkingInQuery(t *testing.T) {
	w, recorder := serveResolve(parkingAdmin, http.MethodGet, "/tariffs?parking_id=8", "")

	assert.Equal(t, http.StatusForbidden, w.Code)
	assert.False(t, recorder.called)
}

func TestResolveAdminParkingsMiddleware_ForbidsOtherParkingInBody(t *testing.T) {
	w, recorder := serveResolve(parkingAdmin, http.MethodPost, "/tariffs", `{"parking_id":8,"name":"x"}`)

	assert.Equal(t, http.StatusForbidden, w.Code)
	assert.False(t, recorder.called)
}

func TestResolveAdminParkingsMiddleware_OwnParkingInBodyKeepsBody(t *testing.T) {
	body := `{"parking_id":7,"name":"x"}`

	w, recorder := serveResolve(parkingAdmin, http.MethodPost, "/tariffs", body)

	require.Equal(t, http.StatusOK, w.Code)
	assert.Equal(t, body, recorder.body, "хендлер получает тело целиком")
}

func TestResolveAdminParkingsMiddleware_NoAdminInContext(t *testing.T) {
	w, recorder := serveResolve(nil, http.MethodGet, "/tariffs", "")

	assert.Equal(t, http.StatusInternalServerError, w.Code)
	assert.False(t, recorder.called)
}
