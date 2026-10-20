package admin

import (
	"context"
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
)

func NewService(
	verifier TokenVerifier,
	repository Repository,
	cacheRepository CacheRepository,
	settings AuthSettings,
) *Service {
	return &Service{
		verifier:        verifier,
		repository:      repository,
		cacheRepository: cacheRepository,
		settings:        settings,
	}
}

// Service Аутентификация администратора по JWT сервиса авторизации и загрузка его прав
type Service struct {
	verifier        TokenVerifier
	repository      Repository
	cacheRepository CacheRepository
	settings        AuthSettings
}

// Authenticate Актуальный администратор по токену: сначала кеш, при промахе — БД с записью в кеш.
// Токен не принят — ErrInvalidToken или ErrUnauthorized; прочая ошибка — сбой хранилища
func (s *Service) Authenticate(ctx context.Context, rawToken string) (*Admin, error) {
	claims, err := s.verifier.Verify(rawToken)
	if err != nil {
		return nil, fmt.Errorf("%w: %w", ErrInvalidToken, err)
	}

	if claims.GuardName != s.settings.GuardName {
		return nil, fmt.Errorf("%w: guard %q is not accepted", ErrUnauthorized, claims.GuardName)
	}

	cached, err := s.cacheRepository.Get(ctx, claims.AdminID)
	if err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "admin_cache"})
	}

	if cached != nil {
		return cached, nil
	}

	admin, err := s.load(ctx, claims.AdminID)
	if err != nil {
		return nil, err
	}

	if err := s.cacheRepository.Set(ctx, admin, s.settings.CacheTTL); err != nil {
		errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "admin_cache"})
	}

	return admin, nil
}

func (s *Service) load(ctx context.Context, adminID uint) (*Admin, error) {
	admin, err := s.repository.GetByID(ctx, adminID)
	if err != nil {
		return nil, err
	}

	if admin == nil || admin.RevokedAt != nil {
		return nil, fmt.Errorf("%w: admin not found or revoked (admin_id=%d)", ErrUnauthorized, adminID)
	}

	admin.Permissions, err = s.repository.GetPermissionNames(ctx, adminID)
	if err != nil {
		return nil, err
	}

	admin.AllowedParkingIDs, err = s.repository.GetAllowedParkingIDs(ctx, adminID)
	if err != nil {
		return nil, err
	}

	return admin, nil
}
