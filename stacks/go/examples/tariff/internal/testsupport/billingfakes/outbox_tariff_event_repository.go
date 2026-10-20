package billingfakes

import (
	"context"
	"sort"
	"time"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// OutboxTariffEventRepository In-memory outboxdomain.Repository для unit-тестов; UpdatedAt — по часам процесса, как now() в БД
type OutboxTariffEventRepository struct {
	Items     map[uint]*outboxdomain.OutboxTariffEvent
	UpdatedAt map[uint]time.Time
	nextID    uint
}

func NewOutboxTariffEventRepository(items ...*outboxdomain.OutboxTariffEvent) *OutboxTariffEventRepository {
	r := &OutboxTariffEventRepository{
		Items:     map[uint]*outboxdomain.OutboxTariffEvent{},
		UpdatedAt: map[uint]time.Time{},
	}

	for _, item := range items {
		r.Items[item.ID] = item
		r.UpdatedAt[item.ID] = time.Now()

		if item.ID > r.nextID {
			r.nextID = item.ID
		}
	}

	return r
}

func (r *OutboxTariffEventRepository) Create(_ context.Context, entity *outboxdomain.OutboxTariffEvent) (*outboxdomain.OutboxTariffEvent, error) {
	r.nextID++
	created := *entity
	created.ID = r.nextID
	r.Items[created.ID] = &created
	r.UpdatedAt[created.ID] = time.Now()

	return &created, nil
}

func (r *OutboxTariffEventRepository) FindByID(_ context.Context, id uint) (*outboxdomain.OutboxTariffEvent, error) {
	item, ok := r.Items[id]
	if !ok {
		return nil, nil
	}

	found := *item

	return &found, nil
}

func (r *OutboxTariffEventRepository) MarkPublished(_ context.Context, id uint) error {
	if item, ok := r.Items[id]; ok && item.IsDeliverable() {
		now := time.Now()
		item.Status = outboxdomain.StatusPublished
		item.PublishedAt = &now
		r.UpdatedAt[id] = now
	}

	return nil
}

func (r *OutboxTariffEventRepository) MarkFailed(_ context.Context, id uint) error {
	r.setStatus(id, outboxdomain.StatusFailed)

	return nil
}

func (r *OutboxTariffEventRepository) MarkAwaitingRetry(_ context.Context, id uint) error {
	r.setStatus(id, outboxdomain.StatusAwaitingRetry)

	return nil
}

func (r *OutboxTariffEventRepository) IncrementAttempts(_ context.Context, id uint) (uint, error) {
	item, ok := r.Items[id]
	if !ok {
		return 0, nil
	}

	item.Attempts++
	r.UpdatedAt[id] = time.Now()

	return item.Attempts, nil
}

func (r *OutboxTariffEventRepository) ClaimForRetry(_ context.Context, limit uint, staleAfter time.Duration) ([]uint, error) {
	now := time.Now()
	ids := make([]uint, 0, len(r.Items))

	for id, item := range r.Items {
		if item.IsDeliverable() && r.UpdatedAt[id].Before(now.Add(-staleAfter)) {
			ids = append(ids, id)
		}
	}

	sort.Slice(ids, func(i, j int) bool { return ids[i] < ids[j] })

	if uint(len(ids)) > limit {
		ids = ids[:limit]
	}

	for _, id := range ids {
		r.UpdatedAt[id] = now
	}

	return ids, nil
}

// MakeStale Сдвигает updated_at события в прошлое — как будто его давно не трогали
func (r *OutboxTariffEventRepository) MakeStale(id uint, age time.Duration) {
	r.UpdatedAt[id] = time.Now().Add(-age)
}

func (r *OutboxTariffEventRepository) setStatus(id uint, status outboxdomain.Status) {
	if item, ok := r.Items[id]; ok {
		item.Status = status
		r.UpdatedAt[id] = time.Now()
	}
}
