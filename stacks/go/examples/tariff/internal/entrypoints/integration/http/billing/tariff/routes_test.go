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

	pass := func(c *gin.Context) { c.Next() }
	SetRoutes(a, router, &Handler{}, AuthMiddlewares{APIKey: pass, Signature: pass})

	var got []string
	for _, route := range router.Routes() {
		got = append(got, route.Method+" "+route.Path)
	}

	assert.Equal(t, []string{"GET /api/integration/v1/billing/tariffs/:id"}, got)
}
