package middleware

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"slices"
	"strconv"
	"strings"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"github.com/gin-gonic/gin"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

const (
	parkingIDParam = "parking_id"
	maxBodyBytes   = 1 << 20
)

type parkingIDsContextKey struct{}

var (
	errBodyNotJSON   = errors.New("тело запроса — только application/json")
	errBodyMalformed = errors.New("тело запроса — один JSON-объект, parking_id не больше одного раза")
	errBodyTooLarge  = errors.New("тело запроса больше 1 МБ")
)

// ResolveAdminParkingsMiddleware Кладёт в контекст парковки, доступные администратору (nil — super-admin, без ограничения),
// и отклоняет parking_id из query или JSON-тела, которого нет среди доступных, — 403.
// Непустое тело не в JSON — 415; больше 1 МБ — 413; не ровно один JSON-объект или parking_id в нём повторён
// (в любом регистре) — 400: иначе хендлер может забиндить не то значение, что проверено здесь.
// Ставится после JWTAuthMiddleware: без администратора в контексте — 500, маршрут собран неверно.
// Маршруты с телом ставят и RequireJSONMiddleware, чтобы хендлер биндил тело как JSON
func ResolveAdminParkingsMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		admin := admindomain.FromContext(c.Request.Context())
		if admin == nil {
			response.ErrorResponse(c, errors.New("resolve admin parkings: no admin in context, JWTAuthMiddleware is missing on the route"))

			return
		}

		parkingIDs := accessibleParkingIDs(admin)

		requestedIDs, err := requestedParkingIDs(c)
		if status, ok := bodyErrorStatus(err); ok {
			response.ErrorResponseUntrackableSentry(c, status, err, nil)

			return
		}

		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		for _, requestedID := range requestedIDs {
			if parkingIDs != nil && !slices.Contains(parkingIDs, requestedID) {
				response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, errors.New("нет доступа к парковке"), nil)

				return
			}
		}

		ctx := context.WithValue(c.Request.Context(), parkingIDsContextKey{}, parkingIDs)
		c.Request = c.Request.WithContext(ctx)

		c.Next()
	}
}

// ParkingIDsFromContext Доступные парковки текущего администратора; nil — без ограничения.
// Нет значения в контексте (маршрут без ResolveAdminParkingsMiddleware) — ошибка, а не «без ограничения»
func ParkingIDsFromContext(ctx context.Context) ([]uint, error) {
	parkingIDs, ok := ctx.Value(parkingIDsContextKey{}).([]uint)
	if !ok {
		return nil, errors.New("parking ids: not in context, ResolveAdminParkingsMiddleware is missing on the route")
	}

	return parkingIDs, nil
}

// accessibleParkingIDs nil — у super-admin; у остальных — непустой или пустой, но не nil срез: администратор без парковок не видит ничего
func accessibleParkingIDs(admin *admindomain.Admin) []uint {
	if slices.Contains(admin.SystemRoles, admindomain.SuperAdminRole) {
		return nil
	}

	return append([]uint{}, admin.AllowedParkingIDs...)
}

// bodyErrorStatus Код ответа на ошибку тела, которую прислал клиент
func bodyErrorStatus(err error) (int, bool) {
	switch {
	case errors.Is(err, errBodyNotJSON):
		return http.StatusUnsupportedMediaType, true
	case errors.Is(err, errBodyTooLarge):
		return http.StatusRequestEntityTooLarge, true
	case errors.Is(err, errBodyMalformed):
		return http.StatusBadRequest, true
	default:
		return 0, false
	}
}

// requestedParkingIDs parking_id из query и JSON-тела; тело возвращается в запрос для хендлера без изменений.
// Значение не число — пропускается: формат проверит валидация хендлера (422)
func requestedParkingIDs(c *gin.Context) ([]uint, error) {
	var ids []uint

	if raw, ok := c.GetQuery(parkingIDParam); ok {
		if id, err := strconv.ParseUint(raw, 10, 64); err == nil {
			ids = append(ids, uint(id))
		}
	}

	if c.Request.Body == nil || c.Request.Body == http.NoBody {
		return ids, nil
	}

	body, err := io.ReadAll(http.MaxBytesReader(c.Writer, c.Request.Body, maxBodyBytes))

	var tooLarge *http.MaxBytesError
	if errors.As(err, &tooLarge) {
		return nil, errBodyTooLarge
	}

	if err != nil {
		return nil, err
	}

	c.Request.Body = io.NopCloser(bytes.NewReader(body))

	if len(body) == 0 {
		return ids, nil
	}

	if c.ContentType() != gin.MIMEJSON {
		return nil, errBodyNotJSON
	}

	bodyID, err := bodyParkingID(body)
	if err != nil {
		return nil, err
	}

	if bodyID != nil {
		ids = append(ids, *bodyID)
	}

	return ids, nil
}

// bodyParkingID parking_id верхнего уровня JSON-объекта. Ключ сравнивается без учёта регистра, как в encoding/json,
// которым биндит хендлер. Тело — не один объект (хвост, второе значение, битый JSON) или ключ повторён — errBodyMalformed
func bodyParkingID(body []byte) (*uint, error) {
	decoder := json.NewDecoder(bytes.NewReader(body))

	if token, err := decoder.Token(); err != nil || token != json.Delim('{') {
		return nil, errBodyMalformed
	}

	var parkingID *uint

	seen := false

	for decoder.More() {
		token, err := decoder.Token()
		if err != nil {
			return nil, errBodyMalformed
		}

		key, ok := token.(string)
		if !ok {
			return nil, errBodyMalformed
		}

		var value json.RawMessage
		if err := decoder.Decode(&value); err != nil {
			return nil, errBodyMalformed
		}

		if !strings.EqualFold(key, parkingIDParam) {
			continue
		}

		if seen {
			return nil, errBodyMalformed
		}

		seen = true

		var id *uint
		if err := json.Unmarshal(value, &id); err == nil {
			parkingID = id
		}
	}

	if token, err := decoder.Token(); err != nil || token != json.Delim('}') {
		return nil, errBodyMalformed
	}

	if _, err := decoder.Token(); !errors.Is(err, io.EOF) {
		return nil, errBodyMalformed
	}

	return parkingID, nil
}
