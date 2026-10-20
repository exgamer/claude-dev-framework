package middleware

import (
	"errors"
	"net/http"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
)

// APIKeyHeaderName Заголовок с ключом партнёра
const APIKeyHeaderName = "Api-Key"

// APIKeyMiddleware Пропускает запрос с ключом партнёра из конфига; ключ не передан или не совпал — 401 без Sentry
func APIKeyMiddleware(service *partnerdomain.Service) gin.HandlerFunc {
	return func(c *gin.Context) {
		// Сравнение ключа — за постоянное время, в сервисе partner
		if err := service.AuthenticateAPIKey(c.GetHeader(APIKeyHeaderName)); err != nil {
			response.ErrorResponseUntrackableSentry(c, http.StatusUnauthorized, errors.New("требуется ключ API"), nil)

			return
		}

		c.Next()
	}
}
