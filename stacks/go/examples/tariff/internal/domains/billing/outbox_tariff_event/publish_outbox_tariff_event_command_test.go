package outbox_tariff_event_test

import (
	"context"
	"errors"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	"example.com/parking-service/internal/testsupport/billingfakes"
)

func pendingEvent(id uint) *outboxdomain.OutboxTariffEvent {
	event := outboxdomain.NewTariffCreatedEvent(outboxdomain.CreatedTariff{TariffID: 10, ParkingID: 1, Name: "Стандарт", Currency: "KZT", Price: 100})
	event.ID = id

	return event
}

func TestPublishOutboxTariffEventCommand_PublishesAndMarksPublished(t *testing.T) {
	repository := billingfakes.NewOutboxTariffEventRepository(pendingEvent(1))
	publisher := &billingfakes.OutboxTariffEventPublisher{}
	command := outboxdomain.NewPublishOutboxTariffEventCommand(repository, publisher, 3)

	err := command.Exec(context.Background(), 1)

	require.NoError(t, err)
	require.Len(t, publisher.Published, 1)
	assert.Equal(t, outboxdomain.RoutingKeyTariffCreated, publisher.Published[0].RoutingKey)
	assert.Equal(t, outboxdomain.StatusPublished, repository.Items[1].Status)
	assert.NotNil(t, repository.Items[1].PublishedAt)
}

func TestPublishOutboxTariffEventCommand_PublishedIsNotSentAgain(t *testing.T) {
	event := pendingEvent(1)
	event.Status = outboxdomain.StatusPublished
	repository := billingfakes.NewOutboxTariffEventRepository(event)
	publisher := &billingfakes.OutboxTariffEventPublisher{}
	command := outboxdomain.NewPublishOutboxTariffEventCommand(repository, publisher, 3)

	err := command.Exec(context.Background(), 1)

	require.NoError(t, err)
	assert.Zero(t, publisher.Calls)
}

func TestPublishOutboxTariffEventCommand_MissingEventIsIgnored(t *testing.T) {
	publisher := &billingfakes.OutboxTariffEventPublisher{}
	command := outboxdomain.NewPublishOutboxTariffEventCommand(billingfakes.NewOutboxTariffEventRepository(), publisher, 3)

	err := command.Exec(context.Background(), 42)

	require.NoError(t, err)
	assert.Zero(t, publisher.Calls)
}

func TestPublishOutboxTariffEventCommand_FailureAwaitsRetry(t *testing.T) {
	repository := billingfakes.NewOutboxTariffEventRepository(pendingEvent(1))
	publisher := &billingfakes.OutboxTariffEventPublisher{Err: errors.New("rabbit unavailable")}
	command := outboxdomain.NewPublishOutboxTariffEventCommand(repository, publisher, 3)

	err := command.Exec(context.Background(), 1)

	require.ErrorIs(t, err, publisher.Err)
	assert.Equal(t, outboxdomain.StatusAwaitingRetry, repository.Items[1].Status)
	assert.Equal(t, uint(1), repository.Items[1].Attempts)
}

func TestPublishOutboxTariffEventCommand_LastAttemptFails(t *testing.T) {
	event := pendingEvent(1)
	event.Status = outboxdomain.StatusAwaitingRetry
	event.Attempts = 2
	repository := billingfakes.NewOutboxTariffEventRepository(event)
	publisher := &billingfakes.OutboxTariffEventPublisher{Err: errors.New("rabbit unavailable")}
	command := outboxdomain.NewPublishOutboxTariffEventCommand(repository, publisher, 3)

	err := command.Exec(context.Background(), 1)

	require.ErrorIs(t, err, publisher.Err)
	assert.Equal(t, outboxdomain.StatusFailed, repository.Items[1].Status)
	assert.Equal(t, uint(3), repository.Items[1].Attempts)

	publisher.Err = nil
	err = command.Exec(context.Background(), 1)

	require.NoError(t, err)
	assert.Empty(t, publisher.Published, "исчерпавшее попытки событие автоматически не повторяется")
}
