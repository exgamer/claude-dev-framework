package tariff

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

type Repository interface {
	// GetByID Чужой тариф — nil, nil. Скоуп парковок: GetByID — аргументом scope, Paginated — полем Search.Scope; All — все парковки, иначе — IDs, пустой IDs — ни одной; остальные методы работают по id/parkingID без скоупа
	GetByID(ctx context.Context, id uint, scope ParkingScope) (*Tariff, error)
	GetDefaultByParkingID(ctx context.Context, parkingID uint) (*Tariff, error)
	Paginated(ctx context.Context, search *Search) (*pagination.Paginated[Tariff], error)
	Create(ctx context.Context, tariff *Tariff) (*Tariff, error)
	Update(ctx context.Context, id uint, patch *Patch) error
	// SetDefault Пишет флаг; второй тариф по умолчанию у парковки (уникальный индекс, параллельная смена) — ErrDefaultTariffTaken
	SetDefault(ctx context.Context, id uint, isDefault bool) error
	// DeactivateByParkingID Снимает активность с активных тарифов парковки; возвращает ID изменённых, уже неактивные не трогает
	DeactivateByParkingID(ctx context.Context, parkingID uint) ([]uint, error)
}

// CacheRepository Кеш тарифов по ID без скоупа парковок
type CacheRepository interface {
	GetByID(ctx context.Context, id uint) (*Tariff, error)
	Set(ctx context.Context, tariff *Tariff) error
	Invalidate(ctx context.Context, id uint) error
}

// PriceLimitRepository Лимиты цен из внешнего реестра парковок; лимит не задан — nil, nil
type PriceLimitRepository interface {
	GetByParkingID(ctx context.Context, parkingID uint) (*PriceLimit, error)
}
