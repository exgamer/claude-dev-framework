package tariff

import (
	"bytes"
	"context"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"

	"example.com/parking-service/internal/common"
	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
	admindomain "example.com/parking-service/internal/domains/identity/admin"
	adminmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
	"example.com/parking-service/internal/testsupport/billingfakes"
	"example.com/parking-service/internal/testsupport/handbookfakes"
	"example.com/parking-service/internal/testsupport/txfakes"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

type noPriceLimits struct{}

func (noPriceLimits) GetByParkingID(context.Context, uint) (*tariffdomain.PriceLimit, error) {
	return nil, nil
}

type noopNotifier struct{}

func (noopNotifier) NotifyCreated(context.Context, *tariffdomain.Tariff) error {
	return nil
}

// replaceBody Подменяет тело после ResolveAdminParkingsMiddleware: хендлер получает не то тело, что проверил middleware
func replaceBody(body string) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Request.Body = io.NopCloser(strings.NewReader(body))

		c.Next()
	}
}

func newCreateHandler(tariffs *billingfakes.TariffRepository) *Handler {
	parkings := handbookfakes.NewParkingRepository(
		&parkingdomain.Parking{ID: 7, IsActive: true, Currency: common.CurrencyKZT},
		&parkingdomain.Parking{ID: 99, IsActive: true, Currency: common.CurrencyKZT},
	)
	outbox := billingfakes.NewOutboxTariffEventRepository()
	txManager := txfakes.NewManager2[tariffdomain.Repository, outboxdomain.Repository](tariffs, outbox)
	publishCommand := outboxdomain.NewPublishOutboxTariffEventCommand(
		outbox,
		&billingfakes.OutboxTariffEventPublisher{},
		txfakes.NewManager[outboxdomain.Repository](outbox),
		3,
	)

	return NewHandler(nil, nil, nil, tariffworkflow.NewCreateTariffWorkflow(txManager, parkings, noPriceLimits{}, noopNotifier{}, publishCommand))
}

// TestHandler_Create_ParkingScope Свой parking_id — 201; чужой — 403 и в middleware, и в хендлере, если тело подменено после проверки
func TestHandler_Create_ParkingScope(t *testing.T) {
	gin.SetMode(gin.TestMode)

	parkingAdmin := &admindomain.Admin{ID: 1, AllowedParkingIDs: []uint{7}}
	ownBody := `{"parking_id":7,"name":"Base","currency":"KZT","price":500}`
	otherBody := `{"parking_id":99,"name":"Base","currency":"KZT","price":500}`

	cases := []struct {
		name        string
		body        string
		replacement string
		want        int
	}{
		{"own parking", ownBody, "", http.StatusCreated},
		{"other parking", otherBody, "", http.StatusForbidden},
		{"other parking after middleware check", ownBody, otherBody, http.StatusForbidden},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			tariffs := billingfakes.NewTariffRepository()

			chain := []gin.HandlerFunc{withAdmin(parkingAdmin), adminmiddleware.ResolveAdminParkingsMiddleware(), adminmiddleware.RequireJSONMiddleware()}
			if tc.replacement != "" {
				chain = append(chain, replaceBody(tc.replacement))
			}

			r := gin.New()
			r.Use(gosdkmiddleware.FormattedResponseMiddleware())
			r.POST("/tariffs", append(chain, newCreateHandler(tariffs).Create())...)

			req := httptest.NewRequest(http.MethodPost, "/tariffs", bytes.NewReader([]byte(tc.body)))
			req.Header.Set("Content-Type", "application/json")

			w := httptest.NewRecorder()
			r.ServeHTTP(w, req)

			assert.Equal(t, tc.want, w.Code, w.Body.String())

			if tc.want != http.StatusCreated {
				assert.Empty(t, tariffs.Items, "тариф не создан")
			}
		})
	}
}
