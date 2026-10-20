package middleware

import (
	"errors"
	"net/http"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
	"example.com/parking-service/pkg/requestbody"
	"example.com/parking-service/pkg/signature"
)

// SignatureSettings Подпись машинного вызова партнёра из конфига
type SignatureSettings struct {
	// Secret Общий HMAC-секрет
	Secret string
	// Tolerance Допустимый разбег часов отправителя и сервиса
	Tolerance time.Duration
}

// SignatureAuthMiddleware HMAC-подпись X-Signature (метод, путь с query, тело), окно времени и nonce; не принята — 401 без Sentry, тело больше 1 МБ — 413, сбой хранилища nonce — 500
func SignatureAuthMiddleware(service *partnerdomain.Service, settings SignatureSettings) gin.HandlerFunc {
	return func(c *gin.Context) {
		// Подпись считается по телу целиком — читать его без предела нельзя; тело возвращается в запрос для хендлера
		body, err := requestbody.ReadLimited(c.Writer, c.Request, requestbody.DefaultLimit)
		if errors.Is(err, requestbody.ErrTooLarge) {
			response.ErrorResponseUntrackableSentry(c, http.StatusRequestEntityTooLarge, errors.New("тело запроса слишком большое"), nil)

			return
		}

		if err != nil {
			response.ErrorResponseUntrackableSentry(c, http.StatusBadRequest, errors.New("тело запроса не прочитано"), nil)

			return
		}

		// Nonce занимается только после проверки подписи: неподписанный запрос чужой nonce не занимает
		parsed, err := verifySignature(c, body, settings)
		if err != nil {
			rejectSignature(c)

			return
		}

		err = service.ConsumeNonce(c.Request.Context(), parsed.Nonce)
		if errors.Is(err, partnerdomain.ErrNonceReplayed) {
			rejectSignature(c)

			return
		}

		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		c.Next()
	}
}

// verifySignature Заголовок разобран, время в окне, подпись совпала
func verifySignature(c *gin.Context, body []byte, settings SignatureSettings) (signature.Parsed, error) {
	parsed, err := signature.Parse(c.GetHeader(signature.HeaderName))
	if err != nil {
		return signature.Parsed{}, err
	}

	err = signature.Verify(signature.VerifyParams{
		Secret: settings.Secret,
		Parsed: parsed,
		Message: signature.Message{
			Method: c.Request.Method,
			Path:   c.Request.URL.RequestURI(),
			Body:   body,
		},
		Tolerance: settings.Tolerance,
		Now:       time.Now(),
	})
	if err != nil {
		return signature.Parsed{}, err
	}

	return parsed, nil
}

func rejectSignature(c *gin.Context) {
	response.ErrorResponseUntrackableSentry(c, http.StatusUnauthorized, errors.New("подпись недействительна"), nil)
}
