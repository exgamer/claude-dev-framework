package tariffs

import (
	"context"
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-request-builder/pkg/builder"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/pkg/requestid"
)

func NewHTTPNotifier(baseURL string, timeout time.Duration) *HTTPNotifier {
	return &HTTPNotifier{
		baseURL: baseURL,
		timeout: timeout,
	}
}

type HTTPNotifier struct {
	baseURL string
	timeout time.Duration
}

func (n *HTTPNotifier) NotifyCreated(ctx context.Context, entity *tariffdomain.Tariff) error {
	// SetJSONBody — после SetRequestHeaders: SetRequestHeaders заменяет карту заголовков вместе с Content-Type
	resp, err := builder.NewPostHttpRequestBuilder[struct{}](ctx, n.baseURL+"/api/v1/tariffs").
		SetRequestHeaders(requestid.Header(ctx)).
		SetJSONBody(tariffFromEntity(entity)).
		SetRequestTimeout(n.timeout).
		SetThrowUnmarshalError(false).
		GetResult()
	if err != nil {
		return fmt.Errorf("notify partnerbilling tariff failed (tariff_id=%d): %w", entity.ID, err)
	}

	if !resp.IsSuccess() {
		return fmt.Errorf("notify partnerbilling tariff failed (tariff_id=%d): status %d", entity.ID, resp.StatusCode)
	}

	return nil
}
