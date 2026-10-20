package middleware

import (
	"bytes"
	"errors"
	"io"
	"net/http"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
	"example.com/parking-service/pkg/signature"
)

// maxSignedBodyBytes Тело больше — 413: подпись считается по телу целиком, читать его без предела нельзя
const maxSignedBodyBytes = 1 << 20

var errBodyTooLarge = errors.New("signed body exceeds limit")

// SignatureSettings Подпись машинного вызова партнёра из конфига
type SignatureSettings struct {
	// Secret Общий HMAC-секрет
	Secret string
	// Tolerance Допустимый разбег часов отправителя и сервиса
	Tolerance time.Duration
}

// SignatureAuthMiddleware Проверяет HMAC-подпись машинного вызова партнёра (заголовок X-Signature: время, nonce, подпись
// метода, пути с query и тела) и окно времени, затем занимает nonce через сервис — неподписанный запрос чужой nonce не занимает;
// ставится после FormattedResponseMiddleware. Тело возвращается в запрос для хендлера.
// Подпись не принята (формат, окно времени, повтор nonce) — 401 без деталей и без Sentry; сбой хранилища nonce — 500
func SignatureAuthMiddleware(service *partnerdomain.Service, settings SignatureSettings) gin.HandlerFunc {
	return func(c *gin.Context) {
		body, err := readBody(c)
		if errors.Is(err, errBodyTooLarge) {
			response.ErrorResponseUntrackableSentry(c, http.StatusRequestEntityTooLarge, errors.New("тело запроса слишком большое"), nil)

			return
		}

		if err != nil {
			response.ErrorResponseUntrackableSentry(c, http.StatusBadRequest, errors.New("тело запроса не прочитано"), nil)

			return
		}

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

func readBody(c *gin.Context) ([]byte, error) {
	if c.Request.Body == nil {
		return nil, nil
	}

	body, err := io.ReadAll(io.LimitReader(c.Request.Body, maxSignedBodyBytes+1))
	if err != nil {
		return nil, err
	}

	if len(body) > maxSignedBodyBytes {
		return nil, errBodyTooLarge
	}

	c.Request.Body = io.NopCloser(bytes.NewReader(body))

	return body, nil
}
