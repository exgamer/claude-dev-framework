package outbox_tariff_event_test

import (
	"context"
	"errors"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	"example.com/parking-service/internal/testsupport/billingfakes"
	"example.com/parking-service/internal/testsupport/txfakes"
)

func newRetryCommand(repository *billingfakes.OutboxTariffEventRepository, publisher *billingfakes.OutboxTariffEventPublisher) *outboxdomain.RetryOutboxTariffEventsCommand {
	publishCommand := outboxdomain.NewPublishOutboxTariffEventCommand(repository, publisher, txfakes.NewManager[outboxdomain.Repository](repository), 3)

	return outboxdomain.NewRetryOutboxTariffEventsCommand(repository, publishCommand, outboxdomain.RetrySettings{BatchSize: 2, StaleAfter: time.Minute})
}

func TestRetryOutboxTariffEventsCommand_PublishesStaleEventsInBatches(t *testing.T) {
	awaiting := pendingEvent(2)
	awaiting.Status = outboxdomain.StatusAwaitingRetry
	repository := billingfakes.NewOutboxTariffEventRepository(pendingEvent(1), awaiting, pendingEvent(3), pendingEvent(4))

	for id := uint(1); id <= 3; id++ {
		repository.MakeStale(id, time.Hour)
	}

	publisher := &billingfakes.OutboxTariffEventPublisher{}
	command := newRetryCommand(repository, publisher)

	result, err := command.Exec(context.Background())

	require.NoError(t, err)
	assert.Equal(t, uint(3), result.Published)
	assert.Len(t, publisher.Published, 3)
	assert.Equal(t, outboxdomain.StatusPending, repository.Items[4].Status, "свежее событие ещё может отправить workflow — не трогается")
}

func TestRetryOutboxTariffEventsCommand_FailureDoesNotStopRun(t *testing.T) {
	repository := billingfakes.NewOutboxTariffEventRepository(pendingEvent(1), pendingEvent(2), pendingEvent(3))

	for id := uint(1); id <= 3; id++ {
		repository.MakeStale(id, time.Hour)
	}

	publisher := &billingfakes.OutboxTariffEventPublisher{Err: errors.New("rabbit unavailable")}
	command := newRetryCommand(repository, publisher)

	result, err := command.Exec(context.Background())

	require.NoError(t, err)
	assert.Equal(t, uint(3), result.Failed)
	assert.Equal(t, 3, publisher.Calls, "каждое событие — одна попытка за прогон: взятые скрыты до следующего")

	for id := uint(1); id <= 3; id++ {
		assert.Equal(t, outboxdomain.StatusAwaitingRetry, repository.Items[id].Status)
	}
}
