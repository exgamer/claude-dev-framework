package admin

import (
	"context"
	"time"
)

// Repository Администраторы и их права из общей БД администраторов; администратор не найден — nil, nil
type Repository interface {
	GetByID(ctx context.Context, id uint) (*Admin, error)
	GetPermissionNames(ctx context.Context, adminID uint) ([]string, error)
	GetAllowedParkingIDs(ctx context.Context, adminID uint) ([]uint, error)
}

// CacheRepository Кеш загруженного администратора с правами; промах — nil, nil
type CacheRepository interface {
	Get(ctx context.Context, adminID uint) (*Admin, error)
	Set(ctx context.Context, admin *Admin, ttl time.Duration) error
}

// TokenVerifier Проверка подписи и стандартных claims JWT
type TokenVerifier interface {
	Verify(rawToken string) (*TokenClaims, error)
}
