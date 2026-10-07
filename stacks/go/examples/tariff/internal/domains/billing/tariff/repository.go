package tariff

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

type Repository interface {
	GetByID(ctx context.Context, id uint) (*Tariff, error)
	GetDefaultByParkingID(ctx context.Context, parkingID uint) (*Tariff, error)
	Paginated(ctx context.Context, search *Search) (*pagination.Paginated[Tariff], error)
	Create(ctx context.Context, tariff *Tariff) (*Tariff, error)
	Update(ctx context.Context, id uint, patch *Patch) error
	SetDefault(ctx context.Context, id uint, isDefault bool) error
}

type CacheRepository interface {
	GetByID(ctx context.Context, id uint) (*Tariff, error)
	Set(ctx context.Context, tariff *Tariff) error
	Invalidate(ctx context.Context, id uint) error
}
