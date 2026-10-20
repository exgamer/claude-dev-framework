package tariff

import (
	"testing"
	"time"

	"github.com/stretchr/testify/assert"

	tariffrabbit "example.com/parking-service/internal/entrypoints/consumer/rabbit/billing/tariff"
)

func TestRetryQueueArguments_ReturnToMainQueueAfterDelay(t *testing.T) {
	arguments := retryQueueArguments(15 * time.Second)

	assert.Equal(t, int64(15000), arguments["x-message-ttl"])
	assert.Equal(t, "", arguments["x-dead-letter-exchange"])
	assert.Equal(t, tariffrabbit.ParkingDeactivatedQueue, arguments["x-dead-letter-routing-key"])
}
