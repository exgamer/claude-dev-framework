package tariff

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

type Repository interface {
	// GetByID Тариф по ID в пределах парковок parkingIDs (nil — без ограничения); чужой — nil, nil
	GetByID(ctx context.Context, id uint, parkingIDs []uint) (*Tariff, error)
	GetDefaultByParkingID(ctx context.Context, parkingID uint) (*Tariff, error)
	// Paginated Список в пределах парковок parkingIDs (nil — без ограничения)
	Paginated(ctx context.Context, search *Search, parkingIDs []uint) (*pagination.Paginated[Tariff], error)
	Create(ctx context.Context, tariff *Tariff) (*Tariff, error)
	Update(ctx context.Context, id uint, patch *Patch) error
	// SetDefault Пишет флаг; второй тариф по умолчанию у парковки (уникальный индекс, параллельная смена) — ErrDefaultTariffTaken
	SetDefault(ctx context.Context, id uint, isDefault bool) error
	// DeactivateByParkingID Снимает активность с активных тарифов парковки; возвращает ID изменённых, уже неактивные не трогает
	DeactivateByParkingID(ctx context.Context, parkingID uint) ([]uint, error)
}

// CacheRepository Кеш тарифов по ID без скоупа: принадлежность парковке сверяет сервис после чтения
type CacheRepository interface {
	GetByID(ctx context.Context, id uint) (*Tariff, error)
	Set(ctx context.Context, tariff *Tariff) error
	Invalidate(ctx context.Context, id uint) error
}

// PriceLimitRepository Лимиты цен из внешнего реестра парковок; лимит не задан — nil, nil
type PriceLimitRepository interface {
	GetByParkingID(ctx context.Context, parkingID uint) (*PriceLimit, error)
}
