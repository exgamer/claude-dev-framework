package tariff

import (
	"context"
	"errors"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/marshaler"
	amqpdriver "github.com/ThreeDotsLabs/watermill-amqp/v2/pkg/amqp"
	"github.com/ThreeDotsLabs/watermill/message"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

const testMaxAttempts = 3

func newTestConsumer(tariffs ...*tariffdomain.Tariff) (*Consumer, *billingfakes.TariffRepository) {
	repository := billingfakes.NewTariffRepository(tariffs...)
	command := tariffdomain.NewDeactivateParkingTariffsCommand(repository, billingfakes.NewTariffCacheRepository())

	return NewConsumer(command, testMaxAttempts), repository
}

// failingTariffRepository Хранилище недоступно
type failingTariffRepository struct {
	*billingfakes.TariffRepository
}

func (r *failingTariffRepository) DeactivateByParkingID(_ context.Context, _ uint) ([]uint, error) {
	return nil, errors.New("postgres unavailable")
}

func newFailingConsumer() *Consumer {
	repository := &failingTariffRepository{TariffRepository: billingfakes.NewTariffRepository()}

	return NewConsumer(tariffdomain.NewDeactivateParkingTariffsCommand(repository, billingfakes.NewTariffCacheRepository()), testMaxAttempts)
}

// deliveredMessage Сообщение, как его отдаёт брокер после returns возвратов из retry-очереди, через маршалер из consumer_registry.go
func deliveredMessage(t *testing.T, payload string, returns int64) *message.Message {
	headers := amqp.Table{amqpdriver.DefaultMessageUUIDHeaderKey: "1"}
	if returns > 0 {
		headers["x-death"] = []interface{}{amqp.Table{"count": returns, "queue": ParkingDeactivatedRetryQueue, "reason": "expired"}}
	}

	msg, err := marshaler.DeathTolerantMarshaler{}.Unmarshal(amqp.Delivery{Headers: headers, Body: []byte(payload)})
	require.NoError(t, err)

	return msg
}

func TestConsumer_DeactivatesParkingTariffs(t *testing.T) {
	consumer, repository := newTestConsumer(tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, true))

	err := consumer.Consume(context.Background(), deliveredMessage(t, `{"parking_id":7}`, 0))

	require.NoError(t, err)
	assert.False(t, repository.Items[1].IsActive())
}

func TestConsumer_BrokenPayloadIsAcked(t *testing.T) {
	consumer, repository := newTestConsumer(tariffdomain.RestoreTariff(tariffdomain.Tariff{ID: 1, ParkingID: 7}, false, true))

	for _, payload := range []string{`not json`, `{}`} {
		err := consumer.Consume(context.Background(), deliveredMessage(t, payload, 0))

		assert.NoError(t, err, "битый payload повтором не исправить — ACK")
	}

	assert.True(t, repository.Items[1].IsActive())
}

func TestDeliveryAttempt_GrowsWithRetryQueueReturns(t *testing.T) {
	for returns, want := range []uint{1, 2, 3} {
		msg := deliveredMessage(t, `{"parking_id":7}`, int64(returns))

		assert.Equal(t, want, deliveryAttempt(msg))
	}
}

func TestConsumer_TemporaryFailureIsNackedToRetryQueue(t *testing.T) {
	consumer := newFailingConsumer()

	for returns := int64(0); returns < testMaxAttempts-1; returns++ {
		err := consumer.Consume(context.Background(), deliveredMessage(t, `{"parking_id":7}`, returns))

		assert.Error(t, err, "попытка %d из %d — NACK, сообщение уходит в retry-очередь", returns+1, testMaxAttempts)
	}
}

func TestConsumer_ExhaustedRetriesAreAcked(t *testing.T) {
	consumer := newFailingConsumer()

	err := consumer.Consume(context.Background(), deliveredMessage(t, `{"parking_id":7}`, testMaxAttempts-1))

	assert.NoError(t, err, "последняя попытка — фиксация в трекере и ACK, без бесконечного цикла")
}
