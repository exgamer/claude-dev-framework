package tariff

import (
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/marshaler"
	amqpdriver "github.com/ThreeDotsLabs/watermill-amqp/v2/pkg/amqp"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestGetConsumers_NackGoesToRetryQueue(t *testing.T) {
	handlers := GetConsumers(&Consumer{})
	require.Len(t, handlers, 1)

	cfg := amqpdriver.Config{}
	for _, apply := range handlers[0].Config {
		apply(&cfg)
	}

	assert.True(t, cfg.Consume.NoRequeueOnNack, "NACK не возвращает сообщение в ту же очередь")
	assert.Equal(t, ParkingDeactivatedRetryQueue, cfg.Queue.Arguments["x-dead-letter-routing-key"])
	assert.Equal(t, "", cfg.Queue.Arguments["x-dead-letter-exchange"])
	assert.IsType(t, marshaler.DeathTolerantMarshaler{}, cfg.Marshaler, "x-death не строка — DefaultMarshaler на нём падает")
}
