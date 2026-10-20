package outbox_tariff_event

import (
	"context"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// fakeSDKPublisher Публикация SDK: release закрыт — возвращает err, иначе ждёт (брокер не подтверждает)
type fakeSDKPublisher struct {
	release chan struct{}
	err     error
	meta    map[string]string
}

func (p *fakeSDKPublisher) PublishWithMetaData(_ string, meta map[string]string, _ any) error {
	<-p.release
	p.meta = meta

	return p.err
}

func newEvent() *outboxdomain.OutboxTariffEvent {
	return &outboxdomain.OutboxTariffEvent{ID: 7, RoutingKey: outboxdomain.RoutingKeyTariffCreated}
}

func TestRabbitPublisher_PublishesWithEventID(t *testing.T) {
	sdk := &fakeSDKPublisher{release: make(chan struct{})}
	close(sdk.release)
	publisher := &RabbitPublisher{publisher: sdk, timeout: time.Second}

	require.NoError(t, publisher.Publish(context.Background(), newEvent()))
	assert.Equal(t, "7", sdk.meta["event_id"])
}

func TestRabbitPublisher_WrapsBrokerError(t *testing.T) {
	sdk := &fakeSDKPublisher{release: make(chan struct{}), err: errors.New("channel closed")}
	close(sdk.release)
	publisher := &RabbitPublisher{publisher: sdk, timeout: time.Second}

	err := publisher.Publish(context.Background(), newEvent())

	require.Error(t, err)
	assert.Contains(t, err.Error(), "outbox_id=7")
}

// TestRabbitPublisher_TimesOutOnHangingBroker Брокер не подтверждает — ошибка по таймауту, вызов не висит (в том числе с контекстом без отмены)
func TestRabbitPublisher_TimesOutOnHangingBroker(t *testing.T) {
	sdk := &fakeSDKPublisher{release: make(chan struct{})}
	defer close(sdk.release)
	publisher := &RabbitPublisher{publisher: sdk, timeout: 20 * time.Millisecond}

	started := time.Now()
	err := publisher.Publish(context.WithoutCancel(context.Background()), newEvent())

	require.ErrorIs(t, err, context.DeadlineExceeded)
	assert.Less(t, time.Since(started), time.Second)
}
