package tariff

import (
	"context"
	"errors"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

func NewService(repository Repository, cacheRepository CacheRepository) *Service {
	return &Service{
		repository:      repository,
		cacheRepository: cacheRepository,
	}
}

type Service struct {
	repository      Repository
	cacheRepository CacheRepository
}

// GetByID Тариф по ID: сначала кеш, при промахе — БД с записью обратно в кеш
func (s *Service) GetByID(ctx context.Context, id uint) (*Tariff, error) {
	tariff, err := s.cacheRepository.GetByID(ctx, id)
	if err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	if tariff != nil {
		return tariff, nil
	}

	tariff, err = s.repository.GetByID(ctx, id)
	if err != nil {
		return nil, err
	}

	if tariff == nil {
		return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
	}

	if err := s.cacheRepository.Set(ctx, tariff); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	return tariff, nil
}

func (s *Service) Paginated(ctx context.Context, search *Search) (*pagination.Paginated[Tariff], error) {
	return s.repository.Paginated(ctx, search)
}

// Create Новый тариф создаётся не дефолтным: дефолт назначается отдельно через SetDefaultTariffCommand
func (s *Service) Create(ctx context.Context, tariff *Tariff) (*Tariff, error) {
	tariff.IsDefault = false

	return s.repository.Create(ctx, tariff)
}

func (s *Service) Update(ctx context.Context, id uint, patch *Patch) (*Tariff, error) {
	if _, err := s.GetByID(ctx, id); err != nil {
		return nil, err
	}

	if err := s.repository.Update(ctx, id, patch); err != nil {
		return nil, err
	}

	if err := s.cacheRepository.Invalidate(ctx, id); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	return s.repository.GetByID(ctx, id)
}
