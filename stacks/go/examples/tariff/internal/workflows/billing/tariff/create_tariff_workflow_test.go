package tariff

import (
	"context"
	"errors"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
	"example.com/parking-service/internal/testsupport/billingfakes"
	"example.com/parking-service/internal/testsupport/handbookfakes"
	"example.com/parking-service/internal/testsupport/txfakes"
)

// fakePriceLimitRepository In-memory tariffdomain.PriceLimitRepository: лимиты по ID парковки
type fakePriceLimitRepository struct {
	limits map[uint]*tariffdomain.PriceLimit
}

func (r *fakePriceLimitRepository) GetByParkingID(_ context.Context, parkingID uint) (*tariffdomain.PriceLimit, error) {
	return r.limits[parkingID], nil
}

// fakeTariffNotifier In-memory TariffNotifier: запоминает отправленное и был ли тариф уже записан в момент отправки
type fakeTariffNotifier struct {
	tariffs        *billingfakes.TariffRepository
	err            error
	sent           []*tariffdomain.Tariff
	storedOnNotify bool
}

func (n *fakeTariffNotifier) NotifyCreated(_ context.Context, tariff *tariffdomain.Tariff) error {
	_, n.storedOnNotify = n.tariffs.Items[tariff.ID]
	n.sent = append(n.sent, tariff)

	return n.err
}

type fixture struct {
	tariffs   *billingfakes.TariffRepository
	outbox    *billingfakes.OutboxTariffEventRepository
	publisher *billingfakes.OutboxTariffEventPublisher
	txManager *txfakes.Manager3[parkingdomain.Repository, tariffdomain.Repository, outboxdomain.Repository]
	notifier  *fakeTariffNotifier
	workflow  *CreateTariffWorkflow
}

func newFixture(parkings *handbookfakes.ParkingRepository, limits ...*tariffdomain.PriceLimit) *fixture {
	tariffs := billingfakes.NewTariffRepository()
	outbox := billingfakes.NewOutboxTariffEventRepository()
	publisher := &billingfakes.OutboxTariffEventPublisher{}
	priceLimits := &fakePriceLimitRepository{limits: map[uint]*tariffdomain.PriceLimit{}}
	notifier := &fakeTariffNotifier{tariffs: tariffs}

	for _, limit := range limits {
		priceLimits.limits[limit.ParkingID] = limit
	}

	txManager := txfakes.NewManager3[parkingdomain.Repository, tariffdomain.Repository, outboxdomain.Repository](parkings, tariffs, outbox)
	publishCommand := outboxdomain.NewPublishOutboxTariffEventCommand(outbox, publisher, 3)

	return &fixture{
		tariffs:   tariffs,
		outbox:    outbox,
		publisher: publisher,
		txManager: txManager,
		notifier:  notifier,
		workflow:  NewCreateTariffWorkflow(txManager, priceLimits, notifier, publishCommand),
	}
}

func activeParking() *handbookfakes.ParkingRepository {
	return handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: true})
}

func TestCreateTariffWorkflow_ParkingNotFound(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository())

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 42, Name: "Стандарт", Price: 100})

	require.Error(t, err)
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

func TestCreateTariffWorkflow_ParkingDisabled(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: false}))

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Price: 100})

	require.Error(t, err)
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

func TestCreateTariffWorkflow_PriceAboveLimit(t *testing.T) {
	f := newFixture(activeParking(), &tariffdomain.PriceLimit{ParkingID: 1, MaxPrice: 500})

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Price: 501})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "price")
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

func TestCreateTariffWorkflow_CreatesAndNotifiesAfterWrite(t *testing.T) {
	f := newFixture(activeParking(), &tariffdomain.PriceLimit{ParkingID: 1, MaxPrice: 500})

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT", Price: 500})

	require.NoError(t, err)
	assert.Equal(t, uint(1), tariff.ParkingID)
	assert.Equal(t, uint(500), tariff.Price)
	assert.False(t, tariff.IsDefault)
	require.Len(t, f.notifier.sent, 1)
	assert.Equal(t, tariff.ID, f.notifier.sent[0].ID)
	assert.True(t, f.notifier.storedOnNotify)
}

func TestCreateTariffWorkflow_NotifyFailureKeepsTariff(t *testing.T) {
	f := newFixture(activeParking())
	f.notifier.err = errors.New("partnerbilling unavailable")

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT", Price: 100})

	require.NoError(t, err)
	assert.Contains(t, f.tariffs.Items, tariff.ID)
	assert.Len(t, f.notifier.sent, 1)
}

func TestCreateTariffWorkflow_WritesOutboxEventInSameTransaction(t *testing.T) {
	f := newFixture(activeParking())
	f.publisher.Err = errors.New("rabbit unavailable")

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT", GraceMinutes: 15, Price: 100})

	require.NoError(t, err)
	assert.True(t, tariff.IsActive)
	assert.Equal(t, 1, f.txManager.Calls)
	require.Len(t, f.outbox.Items, 1)

	for _, event := range f.outbox.Items {
		assert.Equal(t, outboxdomain.RoutingKeyTariffCreated, event.RoutingKey)
		assert.Equal(t, tariff.ID, event.TariffID)
		assert.Equal(t, uint(1), event.ParkingID)
		assert.Equal(t, "Стандарт", event.Name)
		assert.Equal(t, uint(15), event.GraceMinutes)
		assert.Equal(t, uint(100), event.Price)
		// Брокер недоступен: тариф создан, событие ждёт повтора консольной командой
		assert.Equal(t, outboxdomain.StatusAwaitingRetry, event.Status)
		assert.Equal(t, uint(1), event.Attempts)
	}
}

func TestCreateTariffWorkflow_PublishesOutboxEventAfterCommit(t *testing.T) {
	f := newFixture(activeParking())

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT", Price: 100})

	require.NoError(t, err)
	require.Len(t, f.publisher.Published, 1)
	assert.Equal(t, tariff.ID, f.publisher.Published[0].TariffID)

	for _, event := range f.outbox.Items {
		assert.Equal(t, outboxdomain.StatusPublished, event.Status)
	}
}

func TestCreateTariffWorkflow_RejectedWritesNoOutboxEvent(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: false}))

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Price: 100})

	require.Error(t, err)
	assert.Empty(t, f.outbox.Items)
	assert.Zero(t, f.publisher.Calls)
}
