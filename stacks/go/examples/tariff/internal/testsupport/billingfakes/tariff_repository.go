package billingfakes

import (
	"cmp"
	"context"
	"slices"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// TariffRepository In-memory tariffdomain.Repository для unit-тестов
type TariffRepository struct {
	Items map[uint]*tariffdomain.Tariff
	// MakeDefaultErr Ошибка MakeDefault — как отказ уникального индекса или условного UPDATE при параллельной смене; флаг не пишется
	MakeDefaultErr error
	nextID         uint
}

func NewTariffRepository(items ...*tariffdomain.Tariff) *TariffRepository {
	r := &TariffRepository{Items: map[uint]*tariffdomain.Tariff{}}

	for _, item := range items {
		r.Items[item.ID] = item

		if item.ID > r.nextID {
			r.nextID = item.ID
		}
	}

	return r
}

func (r *TariffRepository) GetByID(_ context.Context, id uint, scope tariffdomain.ParkingScope) (*tariffdomain.Tariff, error) {
	item, ok := r.Items[id]
	if !ok || !scope.Allows(item.ParkingID) {
		return nil, nil
	}

	// Копия, как строка из БД: переход в памяти вызывающего не пишет в хранилище
	found := *item

	return &found, nil
}

func (r *TariffRepository) GetDefaultByParkingID(_ context.Context, parkingID uint) (*tariffdomain.Tariff, error) {
	for _, item := range r.Items {
		if item.ParkingID == parkingID && item.IsDefault() {
			found := *item

			return &found, nil
		}
	}

	return nil, nil
}

func (r *TariffRepository) Paginated(_ context.Context, search *tariffdomain.Search) (*pagination.Paginated[tariffdomain.Tariff], error) {
	items := make([]*tariffdomain.Tariff, 0, len(r.Items))

	for _, item := range r.Items {
		if !search.Scope.Allows(item.ParkingID) || (search.ParkingID != 0 && item.ParkingID != search.ParkingID) {
			continue
		}

		items = append(items, item)
	}

	slices.SortFunc(items, func(a, b *tariffdomain.Tariff) int { return cmp.Compare(b.ID, a.ID) })

	return &pagination.Paginated[tariffdomain.Tariff]{Items: items}, nil
}

func (r *TariffRepository) Create(_ context.Context, tariff *tariffdomain.Tariff) (*tariffdomain.Tariff, error) {
	r.nextID++
	created := *tariff
	created.ID = r.nextID
	r.Items[created.ID] = &created

	return &created, nil
}

func (r *TariffRepository) Update(_ context.Context, id uint, patch *tariffdomain.Patch) error {
	item, ok := r.Items[id]
	if !ok {
		return tariffdomain.ErrTariffNotFound
	}

	if patch.Name != nil {
		item.Name = *patch.Name
	}

	if patch.GraceMinutes != nil {
		item.GraceMinutes = *patch.GraceMinutes
	}

	return nil
}

// MakeDefault Условие — как у UPDATE в Postgres: активный и ещё не по умолчанию, иначе ErrTariffStateChanged
func (r *TariffRepository) MakeDefault(_ context.Context, id uint) error {
	if r.MakeDefaultErr != nil {
		return r.MakeDefaultErr
	}

	item, ok := r.Items[id]
	if !ok || item.IsDefault() {
		return tariffdomain.ErrTariffStateChanged
	}

	if err := item.MakeDefault(); err != nil {
		return tariffdomain.ErrTariffStateChanged
	}

	return nil
}

func (r *TariffRepository) UnsetDefault(_ context.Context, id uint) error {
	item, ok := r.Items[id]
	if !ok || !item.IsDefault() {
		return tariffdomain.ErrTariffStateChanged
	}

	item.UnsetDefault()

	return nil
}

func (r *TariffRepository) DeactivateByParkingID(_ context.Context, parkingID uint) ([]uint, error) {
	ids := []uint{}

	for id, item := range r.Items {
		if item.ParkingID == parkingID && item.IsActive() {
			r.Items[id] = tariffdomain.RestoreTariff(*item, item.IsDefault(), false)
			ids = append(ids, item.ID)
		}
	}

	return ids, nil
}

// TariffCacheRepository In-memory tariffdomain.CacheRepository для unit-тестов
type TariffCacheRepository struct {
	Items map[uint]tariffdomain.Tariff
}

func NewTariffCacheRepository() *TariffCacheRepository {
	return &TariffCacheRepository{Items: map[uint]tariffdomain.Tariff{}}
}

func (r *TariffCacheRepository) GetByID(_ context.Context, id uint) (*tariffdomain.Tariff, error) {
	item, ok := r.Items[id]
	if !ok {
		return nil, nil
	}

	return &item, nil
}

func (r *TariffCacheRepository) Set(_ context.Context, tariff *tariffdomain.Tariff) error {
	r.Items[tariff.ID] = *tariff

	return nil
}

func (r *TariffCacheRepository) Invalidate(_ context.Context, ids ...uint) error {
	for _, id := range ids {
		delete(r.Items, id)
	}

	return nil
}
