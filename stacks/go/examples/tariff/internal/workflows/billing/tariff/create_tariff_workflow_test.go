package tariff

import (
	"context"
	"errors"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"example.com/parking-service/internal/common"
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
	steps  *steps
}

func (r *fakePriceLimitRepository) GetByParkingID(_ context.Context, parkingID uint) (*tariffdomain.PriceLimit, error) {
	r.steps.add("pricelimit:get")

	return r.limits[parkingID], nil
}

// fakeTariffNotifier In-memory TariffNotifier: запоминает отправленное и был ли тариф уже записан в момент отправки
type fakeTariffNotifier struct {
	tariffs        *billingfakes.TariffRepository
	steps          *steps
	err            error
	sent           []*tariffdomain.Tariff
	storedOnNotify bool
}

func (n *fakeTariffNotifier) NotifyCreated(_ context.Context, tariff *tariffdomain.Tariff) error {
	n.steps.add("partner:notify")
	_, n.storedOnNotify = n.tariffs.Items[tariff.ID]
	n.sent = append(n.sent, tariff)

	return n.err
}

// steps Журнал шагов сценария: порядок проверки, транзакции и действий после коммита
type steps struct {
	items []string
}

func (s *steps) add(step string) {
	if s != nil {
		s.items = append(s.items, step)
	}
}

// recordingParkingRepository Пишет в журнал чтение парковки
type recordingParkingRepository struct {
	parkingdomain.Repository
	steps *steps
}

func (r *recordingParkingRepository) GetByID(ctx context.Context, id uint) (*parkingdomain.Parking, error) {
	r.steps.add("parking:get")

	return r.Repository.GetByID(ctx, id)
}

// recordingTariffRepository Пишет в журнал создание тарифа
type recordingTariffRepository struct {
	*billingfakes.TariffRepository
	steps *steps
}

func (r *recordingTariffRepository) Create(ctx context.Context, tariff *tariffdomain.Tariff) (*tariffdomain.Tariff, error) {
	r.steps.add("tariff:create")

	return r.TariffRepository.Create(ctx, tariff)
}

// recordingOutboxRepository Пишет в журнал запись события в outbox
type recordingOutboxRepository struct {
	*billingfakes.OutboxTariffEventRepository
	steps *steps
}

func (r *recordingOutboxRepository) Create(ctx context.Context, event *outboxdomain.OutboxTariffEvent) (*outboxdomain.OutboxTariffEvent, error) {
	r.steps.add("outbox:create")

	return r.OutboxTariffEventRepository.Create(ctx, event)
}

// recordingTxManager Пишет в журнал начало и коммит транзакции поверх txfakes.Manager2
type recordingTxManager struct {
	*txfakes.Manager2[tariffdomain.Repository, outboxdomain.Repository]
	steps *steps
}

func (m *recordingTxManager) Exec(ctx context.Context, fn func(ctx context.Context, tariffRepository tariffdomain.Repository, outboxRepository outboxdomain.Repository) error) error {
	m.steps.add("tx:begin")

	if err := m.Manager2.Exec(ctx, fn); err != nil {
		return err
	}

	m.steps.add("tx:commit")

	return nil
}

// recordingPublisher Пишет в журнал публикацию события
type recordingPublisher struct {
	*billingfakes.OutboxTariffEventPublisher
	steps *steps
}

func (p *recordingPublisher) Publish(ctx context.Context, event *outboxdomain.OutboxTariffEvent) error {
	p.steps.add("broker:publish")

	return p.OutboxTariffEventPublisher.Publish(ctx, event)
}

type fixture struct {
	tariffs   *billingfakes.TariffRepository
	outbox    *billingfakes.OutboxTariffEventRepository
	publisher *billingfakes.OutboxTariffEventPublisher
	txManager *recordingTxManager
	notifier  *fakeTariffNotifier
	steps     *steps
	workflow  *CreateTariffWorkflow
}

func newFixture(parkings *handbookfakes.ParkingRepository, limits ...*tariffdomain.PriceLimit) *fixture {
	log := &steps{}
	tariffs := billingfakes.NewTariffRepository()
	outbox := billingfakes.NewOutboxTariffEventRepository()
	publisher := &billingfakes.OutboxTariffEventPublisher{}
	priceLimits := &fakePriceLimitRepository{limits: map[uint]*tariffdomain.PriceLimit{}, steps: log}
	notifier := &fakeTariffNotifier{tariffs: tariffs, steps: log}

	for _, limit := range limits {
		priceLimits.limits[limit.ParkingID] = limit
	}

	recordedTariffs := &recordingTariffRepository{TariffRepository: tariffs, steps: log}
	recordedOutbox := &recordingOutboxRepository{OutboxTariffEventRepository: outbox, steps: log}
	txManager := &recordingTxManager{
		Manager2: txfakes.NewManager2[tariffdomain.Repository, outboxdomain.Repository](recordedTariffs, recordedOutbox),
		steps:    log,
	}
	publishCommand := outboxdomain.NewPublishOutboxTariffEventCommand(
		outbox,
		&recordingPublisher{OutboxTariffEventPublisher: publisher, steps: log},
		txfakes.NewManager[outboxdomain.Repository](outbox),
		3,
	)

	return &fixture{
		tariffs:   tariffs,
		outbox:    outbox,
		publisher: publisher,
		txManager: txManager,
		notifier:  notifier,
		steps:     log,
		workflow: NewCreateTariffWorkflow(
			txManager,
			&recordingParkingRepository{Repository: parkings, steps: log},
			priceLimits,
			notifier,
			publishCommand,
		),
	}
}

func activeParking() *handbookfakes.ParkingRepository {
	return handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: true, Currency: common.CurrencyKZT})
}

func TestCreateTariffWorkflow_ParkingNotFound(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository())

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 42, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "parking_id")
	assert.Zero(t, f.txManager.Calls, "транзакция не открывается")
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

// TestCreateTariffWorkflow_ParkingDisabled Отключённая парковка — 422 по parking_id (состояние данных, не доступ), реестр цен не вызывается
func TestCreateTariffWorkflow_ParkingDisabled(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: false, Currency: common.CurrencyKZT}))

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "parking_id")
	assert.Equal(t, []string{"parking:get"}, f.steps.items, "ни реестр цен, ни транзакция не тронуты")
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

func TestCreateTariffWorkflow_PriceAboveLimit(t *testing.T) {
	f := newFixture(activeParking(), &tariffdomain.PriceLimit{ParkingID: 1, MaxPrice: 500})

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 501})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "price")
	assert.Empty(t, f.tariffs.Items)
	assert.Empty(t, f.notifier.sent)
}

func TestCreateTariffWorkflow_InvariantsCheckedBeforeAnyRead(t *testing.T) {
	f := newFixture(activeParking())

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: " ", Currency: "EUR", Price: 100})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "name")
	assert.Contains(t, appException.Context, "currency")
	assert.Empty(t, f.steps.items, "ни парковка, ни транзакция не тронуты")
}

func TestCreateTariffWorkflow_CurrencyOtherThanParkings(t *testing.T) {
	f := newFixture(activeParking())

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyUSD, Price: 100})

	var appException *exception.AppException
	require.ErrorAs(t, err, &appException)
	assert.Equal(t, exception.ErrorKindValidation, appException.Kind)
	assert.Contains(t, appException.Context, "currency")
	assert.Zero(t, f.txManager.Calls, "транзакция не открывается")
	assert.Empty(t, f.tariffs.Items)
}

func TestCreateTariffWorkflow_CreatesAndNotifiesAfterWrite(t *testing.T) {
	f := newFixture(activeParking(), &tariffdomain.PriceLimit{ParkingID: 1, MaxPrice: 500})

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 500})

	require.NoError(t, err)
	assert.Equal(t, uint(1), tariff.ParkingID)
	assert.Equal(t, uint(500), tariff.Price)
	assert.False(t, tariff.IsDefault())
	require.Len(t, f.notifier.sent, 1)
	assert.Equal(t, tariff.ID, f.notifier.sent[0].ID)
	assert.True(t, f.notifier.storedOnNotify)
}

func TestCreateTariffWorkflow_NotifyFailureKeepsTariff(t *testing.T) {
	f := newFixture(activeParking())
	f.notifier.err = errors.New("partnerbilling unavailable")

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	require.NoError(t, err)
	assert.Contains(t, f.tariffs.Items, tariff.ID)
	assert.Len(t, f.notifier.sent, 1)
}

func TestCreateTariffWorkflow_WritesOutboxEventInSameTransaction(t *testing.T) {
	f := newFixture(activeParking())
	f.publisher.Err = errors.New("rabbit unavailable")

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, GraceMinutes: 15, Price: 100})

	require.NoError(t, err)
	assert.True(t, tariff.IsActive())
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
		assert.Equal(t, outboxdomain.StatusAwaitingRetry, event.Status())
		assert.Equal(t, uint(1), event.Attempts)
	}
}

func TestCreateTariffWorkflow_PublishesOutboxEventAfterCommit(t *testing.T) {
	f := newFixture(activeParking())

	tariff, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	require.NoError(t, err)
	require.Len(t, f.publisher.Published, 1)
	assert.Equal(t, tariff.ID, f.publisher.Published[0].TariffID)

	for _, event := range f.outbox.Items {
		assert.Equal(t, outboxdomain.StatusPublished, event.Status())
	}
}

func TestCreateTariffWorkflow_RejectedWritesNoOutboxEvent(t *testing.T) {
	f := newFixture(handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: false, Currency: common.CurrencyKZT}))

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	require.Error(t, err)
	assert.Empty(t, f.outbox.Items)
	assert.Zero(t, f.publisher.Calls)
}

// TestCreateTariffWorkflow_StepsOrder Проверка парковки (своя БД) — до внешнего реестра цен, оба — до транзакции; в транзакции тариф и outbox; партнёр и брокер — после коммита
func TestCreateTariffWorkflow_StepsOrder(t *testing.T) {
	f := newFixture(activeParking())

	_, err := f.workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: common.CurrencyKZT, Price: 100})

	require.NoError(t, err)
	assert.Equal(t, []string{
		"parking:get",
		"pricelimit:get",
		"tx:begin",
		"tariff:create",
		"outbox:create",
		"tx:commit",
		"partner:notify",
		"broker:publish",
	}, f.steps.items)
}
