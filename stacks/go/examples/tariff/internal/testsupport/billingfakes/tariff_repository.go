package billingfakes

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// TariffRepository In-memory tariffdomain.Repository для unit-тестов
type TariffRepository struct {
	Items  map[uint]*tariffdomain.Tariff
	nextID uint
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

func (r *TariffRepository) GetByID(_ context.Context, id uint) (*tariffdomain.Tariff, error) {
	return r.Items[id], nil
}

func (r *TariffRepository) GetDefaultByParkingID(_ context.Context, parkingID uint) (*tariffdomain.Tariff, error) {
	for _, item := range r.Items {
		if item.ParkingID == parkingID && item.IsDefault {
			return item, nil
		}
	}

	return nil, nil
}

func (r *TariffRepository) Paginated(_ context.Context, _ *tariffdomain.Search) (*pagination.Paginated[tariffdomain.Tariff], error) {
	items := make([]*tariffdomain.Tariff, 0, len(r.Items))

	for _, item := range r.Items {
		items = append(items, item)
	}

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
		return nil
	}

	if patch.Name != nil {
		item.Name = *patch.Name
	}

	if patch.GraceMinutes != nil {
		item.GraceMinutes = *patch.GraceMinutes
	}

	return nil
}

func (r *TariffRepository) SetDefault(_ context.Context, id uint, isDefault bool) error {
	if item, ok := r.Items[id]; ok {
		item.IsDefault = isDefault
	}

	return nil
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

func (r *TariffCacheRepository) Invalidate(_ context.Context, id uint) error {
	delete(r.Items, id)

	return nil
}
