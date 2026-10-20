package pricelimits

import (
	"context"
	"fmt"
	"net/http"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-request-builder/pkg/builder"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/pkg/requestid"
)

func NewHTTPRepository(baseURL string, timeout time.Duration) *HTTPRepository {
	return &HTTPRepository{
		baseURL: baseURL,
		timeout: timeout,
	}
}

type HTTPRepository struct {
	baseURL string
	timeout time.Duration
}

func (r *HTTPRepository) GetByParkingID(ctx context.Context, parkingID uint) (*tariffdomain.PriceLimit, error) {
	// Тело ответа с ошибкой бывает не JSON: без SetThrowUnmarshalError(false) сбой разбора скроет статус
	resp, err := builder.NewGetHttpRequestBuilder[builder.Response[priceLimit]](
		ctx,
		fmt.Sprintf("%s/api/v1/parkings/%d/price-limit", r.baseURL, parkingID),
	).
		SetRequestHeaders(requestid.Header(ctx)).
		SetRequestTimeout(r.timeout).
		SetThrowUnmarshalError(false).
		GetResult()
	if err != nil {
		return nil, fmt.Errorf("get cityparking price limit failed (parking_id=%d): %w", parkingID, err)
	}

	// 404 — реестр не ограничивает цену на этой парковке
	if resp.StatusCode == http.StatusNotFound {
		return nil, nil
	}

	if !resp.IsSuccess() || !resp.Result.Success {
		return nil, fmt.Errorf("get cityparking price limit failed (parking_id=%d): status %d", parkingID, resp.StatusCode)
	}

	return priceLimitToEntity(&resp.Result.Data), nil
}
