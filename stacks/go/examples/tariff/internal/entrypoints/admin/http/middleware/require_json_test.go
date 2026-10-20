package middleware

import (
	"bytes"
	"mime/multipart"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin/validation"
	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"example.com/parking-service/pkg/requestbody"
)

// scopedBody Тело с parking_id, как у запроса создания тарифа
type scopedBody struct {
	validation.Request
	ParkingID uint `json:"parking_id" binding:"required"`
}

// serveScopedCreate Цепочка маршрута создания: скоуп, только JSON, биндинг хендлера через ValidateRequestBody
func serveScopedCreate(target, contentType string, body []byte) (*httptest.ResponseRecorder, *uint) {
	gin.SetMode(gin.TestMode)

	var bound *uint

	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.POST("/tariffs", withAdmin(parkingAdmin), ResolveAdminParkingsMiddleware(), RequireJSONMiddleware(), func(c *gin.Context) {
		request := scopedBody{}
		if ok := validators.ValidateRequestBody(c, &request); !ok {
			return
		}

		bound = &request.ParkingID
		c.Status(http.StatusOK)
	})

	req := httptest.NewRequest(http.MethodPost, target, bytes.NewReader(body))
	if contentType != "" {
		req.Header.Set("Content-Type", contentType)
	}

	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	return w, bound
}

func multipartBody(t *testing.T, field, value string) (string, []byte) {
	t.Helper()

	var buf bytes.Buffer

	writer := multipart.NewWriter(&buf)
	require.NoError(t, writer.WriteField(field, value))
	require.NoError(t, writer.Close())

	return writer.FormDataContentType(), buf.Bytes()
}

func TestScopedCreate_OtherParkingCannotBypassScope(t *testing.T) {
	multipartType, multipartPayload := multipartBody(t, "ParkingID", "99")
	oversized := []byte(`{"parking_id":7,"name":"` + strings.Repeat("a", requestbody.DefaultLimit) + `"}`)

	cases := []struct {
		name        string
		target      string
		contentType string
		body        []byte
		wantCode    int
	}{
		{"форма", "/tariffs", "application/x-www-form-urlencoded", []byte("ParkingID=99"), http.StatusUnsupportedMediaType},
		{"форма с parking_id", "/tariffs", "application/x-www-form-urlencoded", []byte("parking_id=99"), http.StatusUnsupportedMediaType},
		{"multipart", "/tariffs", multipartType, multipartPayload, http.StatusUnsupportedMediaType},
		{"query без тела и Content-Type", "/tariffs?ParkingID=99", "", nil, http.StatusUnsupportedMediaType},
		{"query parking_id при JSON-теле своей парковки", "/tariffs?parking_id=99", "application/json", []byte(`{"parking_id":7}`), http.StatusForbidden},
		{"JSON другой парковки", "/tariffs", "application/json", []byte(`{"parking_id":99}`), http.StatusForbidden},
		{"ключ в другом регистре", "/tariffs", "application/json", []byte(`{"PARKING_ID":99}`), http.StatusForbidden},
		{"ключ с K в виде знака кельвина", "/tariffs", "application/json", []byte(`{"par\u212aing_id":99}`), http.StatusForbidden},
		{"ключ повторён в другом регистре", "/tariffs", "application/json", []byte(`{"parking_id":7,"Parking_ID":99}`), http.StatusBadRequest},
		{"ключ повторён", "/tariffs", "application/json", []byte(`{"parking_id":7,"parking_id":99}`), http.StatusBadRequest},
		{"хвост после объекта", "/tariffs", "application/json", []byte(`{"parking_id":99}x`), http.StatusBadRequest},
		{"два объекта, первый чужой", "/tariffs", "application/json", []byte(`{"parking_id":99}{"parking_id":7}`), http.StatusBadRequest},
		{"два объекта, первый свой", "/tariffs", "application/json", []byte(`{"parking_id":7}{"parking_id":99}`), http.StatusBadRequest},
		{"битый JSON", "/tariffs", "application/json", []byte(`{"parking_id":99`), http.StatusBadRequest},
		{"массив вместо объекта", "/tariffs", "application/json", []byte(`[{"parking_id":99}]`), http.StatusBadRequest},
		{"null вместо объекта", "/tariffs", "application/json", []byte(`null`), http.StatusBadRequest},
		{"тело больше 1 МБ", "/tariffs", "application/json", oversized, http.StatusRequestEntityTooLarge},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			w, bound := serveScopedCreate(tc.target, tc.contentType, tc.body)

			assert.Equal(t, tc.wantCode, w.Code)
			assert.Nil(t, bound, "хендлер не вызван")
		})
	}
}

func TestScopedCreate_OwnParkingInJSONBinds(t *testing.T) {
	for _, body := range []string{`{"parking_id":7}`, " {\"name\":\"x\",\"parking_id\":7} \n"} {
		w, bound := serveScopedCreate("/tariffs", "application/json; charset=utf-8", []byte(body))

		require.Equal(t, http.StatusOK, w.Code, body)
		require.NotNil(t, bound)
		assert.Equal(t, uint(7), *bound)
	}
}

func TestResolveAdminParkingsMiddleware_NonJSONBodyIsUnsupported(t *testing.T) {
	gin.SetMode(gin.TestMode)

	recorder := &scopeRecorder{}
	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.POST("/tariffs", withAdmin(parkingAdmin), ResolveAdminParkingsMiddleware(), recorder.handler)

	req := httptest.NewRequest(http.MethodPost, "/tariffs", strings.NewReader("ParkingID=99"))
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")

	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	assert.Equal(t, http.StatusUnsupportedMediaType, w.Code)
	assert.False(t, recorder.called)
}

func TestResolveAdminParkingsMiddleware_EmptyPostWithoutContentTypePasses(t *testing.T) {
	w, recorder := serveResolve(parkingAdmin, http.MethodPost, "/tariffs", "")

	require.Equal(t, http.StatusOK, w.Code, "set-default: POST без тела")
	assert.True(t, recorder.called)
}
