package middleware

import (
	"errors"
	"net/http"
	"strings"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/constants"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

const bearerScheme = "bearer"

// JWTAuthMiddleware Проверяет Bearer JWT сервиса авторизации и кладёт администратора в контекст запроса; ставится после FormattedResponseMiddleware.
// Токен не принят — 401 без отправки в Sentry и без деталей проверки в ответе; сбой хранилища — 500
func JWTAuthMiddleware(service *admindomain.Service) gin.HandlerFunc {
	return func(c *gin.Context) {
		token, ok := bearerToken(c.GetHeader(constants.AuthorizationHeaderName))
		if !ok {
			response.ErrorResponseUntrackableSentry(c, http.StatusUnauthorized, errors.New("требуется авторизация"), nil)

			return
		}

		admin, err := service.Authenticate(c.Request.Context(), token)
		if errors.Is(err, admindomain.ErrInvalidToken) || errors.Is(err, admindomain.ErrUnauthorized) {
			response.ErrorResponseUntrackableSentry(c, http.StatusUnauthorized, errors.New("токен недействителен"), nil)

			return
		}

		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		ctx := admindomain.WithAdmin(c.Request.Context(), admin)
		c.Request = c.Request.WithContext(ctx)

		c.Next()
	}
}

// bearerToken Токен из заголовка Authorization; схема сравнивается без учёта регистра (RFC 7235)
func bearerToken(header string) (string, bool) {
	scheme, token, found := strings.Cut(header, " ")
	if !found || !strings.EqualFold(scheme, bearerScheme) {
		return "", false
	}

	token = strings.TrimSpace(token)
	if token == "" {
		return "", false
	}

	return token, true
}
