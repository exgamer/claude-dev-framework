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

// GetByID Тариф по ID: сначала кеш, при промахе — БД с записью обратно в кеш; чужой тариф — 404, в том числе из кеша
func (s *Service) GetByID(ctx context.Context, id uint, scope ParkingScope) (*Tariff, error) {
	tariff, err := s.cacheRepository.GetByID(ctx, id)
	if err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	if tariff != nil {
		if !scope.Allows(tariff.ParkingID) {
			return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
		}

		return tariff, nil
	}

	tariff, err = s.repository.GetByID(ctx, id, scope)
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

// Update Нарушение инвариантов патча — 422; чужой или удалённый тариф — 404
func (s *Service) Update(ctx context.Context, id uint, scope ParkingScope, patch *Patch) (*Tariff, error) {
	if fieldErrors := ValidatePatch(patch); len(fieldErrors) > 0 {
		return nil, exception.NewValidationException(fieldErrors, false)
	}

	if _, err := s.GetByID(ctx, id, scope); err != nil {
		return nil, err
	}

	// Тариф удалён между чтением и записью — 404, как не найденный
	err := s.repository.Update(ctx, id, patch)
	if errors.Is(err, ErrTariffNotFound) {
		return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
	}

	if err != nil {
		return nil, err
	}

	if err := s.cacheRepository.Invalidate(ctx, id); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	updated, err := s.repository.GetByID(ctx, id, scope)
	if err != nil {
		return nil, err
	}

	if updated == nil {
		return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
	}

	return updated, nil
}
