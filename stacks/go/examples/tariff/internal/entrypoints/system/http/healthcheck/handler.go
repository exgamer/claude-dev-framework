package healthcheck

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"
)

// probeTimeout Предел одной проверки зависимости: зависшая БД не держит пробу дольше
const probeTimeout = 2 * time.Second

// Dependency Обязательная зависимость для readiness: недоступна — сервис не принимает трафик
type Dependency struct {
	Name  string
	Probe func(ctx context.Context) error
}

func NewHandler(dependencies ...Dependency) *Handler {
	return &Handler{dependencies: dependencies}
}

type Handler struct {
	dependencies []Dependency
}

// @Summary		Liveness probe
// @Description	Процесс запущен; зависимости не проверяются
// @Tags			system-healthcheck
// @Produce		json
// @Success		200	{object}	statusResponse
// @Router			/healthcheck/liveness [get]
func (h *Handler) Live() gin.HandlerFunc {
	return func(c *gin.Context) {
		response.Success(c, statusData{Status: "ok"})
	}
}

// @Summary		Readiness probe
// @Description	Сервис готов принимать трафик: Postgres, Redis и RabbitMQ доступны
// @Tags			system-healthcheck
// @Produce		json
// @Success		200	{object}	statusResponse
// @Failure		503	{object}	structures.InternalServerResponse
// @Router			/healthcheck/readiness [get]
func (h *Handler) Ready() gin.HandlerFunc {
	return func(c *gin.Context) {
		for _, dependency := range h.dependencies {
			if err := probe(c.Request.Context(), dependency); err != nil {
				// Проба повторяется каждые несколько секунд — в Sentry не отправляем, сбой виден по 503 и в логах вызовов
				response.ErrorResponseUntrackableSentry(c, http.StatusServiceUnavailable, err, nil)

				return
			}
		}

		response.Success(c, statusData{Status: "ok"})
	}
}

func probe(ctx context.Context, dependency Dependency) error {
	ctx, cancel := context.WithTimeout(ctx, probeTimeout)
	defer cancel()

	if err := dependency.Probe(ctx); err != nil {
		return fmt.Errorf("%s unavailable", dependency.Name)
	}

	return nil
}
