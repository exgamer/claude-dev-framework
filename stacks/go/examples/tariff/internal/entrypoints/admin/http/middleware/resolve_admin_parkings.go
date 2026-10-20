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

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	admindomain "example.com/parking-service/internal/domains/identity/admin"
	"example.com/parking-service/pkg/requestbody"
)

const parkingIDParam = "parking_id"

type parkingScopeContextKey struct{}

var (
	errBodyNotJSON   = errors.New("тело запроса — только application/json")
	errBodyMalformed = errors.New("тело запроса — один JSON-объект, parking_id не больше одного раза")
	errBodyTooLarge  = errors.New("тело запроса больше 1 МБ")
)

// ResolveAdminParkingsMiddleware Скоуп парковок администратора в контекст; чужой parking_id — 403, тело не JSON — 415, больше 1 МБ — 413, не один объект или повтор parking_id — 400.
// Ставится после JWTAuthMiddleware: без администратора в контексте — 500, маршрут собран неверно
func ResolveAdminParkingsMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		admin := admindomain.FromContext(c.Request.Context())
		if admin == nil {
			response.ErrorResponse(c, errors.New("resolve admin parkings: no admin in context, JWTAuthMiddleware is missing on the route"))

			return
		}

		scope := accessibleParkingScope(admin)

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
			if !scope.Allows(requestedID) {
				response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, errors.New("нет доступа к парковке"), nil)

				return
			}
		}

		ctx := context.WithValue(c.Request.Context(), parkingScopeContextKey{}, scope)
		c.Request = c.Request.WithContext(ctx)

		c.Next()
	}
}

// ParkingScopeFromContext Скоуп парковок текущего администратора; нет значения в контексте — ошибка: маршрут собран без ResolveAdminParkingsMiddleware
func ParkingScopeFromContext(ctx context.Context) (tariffdomain.ParkingScope, error) {
	scope, ok := ctx.Value(parkingScopeContextKey{}).(tariffdomain.ParkingScope)
	if !ok {
		return tariffdomain.ParkingScope{}, errors.New("parking scope: not in context, ResolveAdminParkingsMiddleware is missing on the route")
	}

	return scope, nil
}

// accessibleParkingScope All: true — только у super-admin; у остальных — их парковки: администратор без парковок не видит ничего
func accessibleParkingScope(admin *admindomain.Admin) tariffdomain.ParkingScope {
	if slices.Contains(admin.SystemRoles, admindomain.SuperAdminRole) {
		return tariffdomain.ParkingScope{All: true}
	}

	return tariffdomain.ParkingScope{IDs: append([]uint{}, admin.AllowedParkingIDs...)}
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

	body, err := requestbody.ReadLimited(c.Writer, c.Request, requestbody.DefaultLimit)
	if errors.Is(err, requestbody.ErrTooLarge) {
		return nil, errBodyTooLarge
	}

	if err != nil {
		return nil, err
	}

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
