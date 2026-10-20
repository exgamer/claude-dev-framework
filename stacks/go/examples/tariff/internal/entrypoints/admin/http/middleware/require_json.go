package middleware

import (
	"errors"
	"net/http"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"
)

// RequireJSONMiddleware Пропускает только тело application/json, иначе — 415
func RequireJSONMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// ValidateRequestBody выбирает биндинг по Content-Type: форма или запрос без Content-Type биндятся из формы и query
		// по имени поля, мимо проверки parking_id в ResolveAdminParkingsMiddleware
		if c.ContentType() != gin.MIMEJSON {
			response.ErrorResponseUntrackableSentry(c, http.StatusUnsupportedMediaType, errors.New("тело запроса — только application/json"), nil)

			return
		}

		c.Next()
	}
}
