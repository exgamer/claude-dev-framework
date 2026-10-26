package tariff

import (
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/di"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

// Пути — по схеме /api/<context>/<version>/<domain>/<entity>
func TestSetRoutes_PathsFollowRegulation(t *testing.T) {
	gin.SetMode(gin.TestMode)

	router := gin.New()
	a := &app.App{Container: di.NewContainer()}

	SetRoutes(a, router, &Handler{}, func(c *gin.Context) { c.Next() })

	var got []string
	for _, route := range router.Routes() {
		got = append(got, route.Method+" "+route.Path)
	}

	assert.ElementsMatch(t, []string{
		"GET /api/admin/v1/billing/tariffs",
		"GET /api/admin/v1/billing/tariffs/default",
		"GET /api/admin/v1/billing/tariffs/:id",
		"POST /api/admin/v1/billing/tariffs",
		"PATCH /api/admin/v1/billing/tariffs/:id",
		"POST /api/admin/v1/billing/tariffs/:id/set-default",
	}, got)
}
