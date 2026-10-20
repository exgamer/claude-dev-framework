package tariff

import (
	"context"
	"errors"
	"slices"

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

// GetByID Тариф по ID в пределах парковок parkingIDs (nil — без ограничения): сначала кеш, при промахе — БД с записью обратно в кеш.
// Кеш хранит тариф без скоупа — после чтения из кеша парковка сверяется со скоупом; чужой тариф — 404, как из БД
func (s *Service) GetByID(ctx context.Context, id uint, parkingIDs []uint) (*Tariff, error) {
	tariff, err := s.cacheRepository.GetByID(ctx, id)
	if err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	if tariff != nil {
		if !inParkings(parkingIDs, tariff.ParkingID) {
			return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
		}

		return tariff, nil
	}

	tariff, err = s.repository.GetByID(ctx, id, parkingIDs)
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

// Paginated Список тарифов в пределах парковок parkingIDs (nil — без ограничения)
func (s *Service) Paginated(ctx context.Context, search *Search, parkingIDs []uint) (*pagination.Paginated[Tariff], error) {
	return s.repository.Paginated(ctx, search, parkingIDs)
}

// Update Изменение тарифа в пределах парковок parkingIDs (nil — без ограничения); чужой — 404
func (s *Service) Update(ctx context.Context, id uint, parkingIDs []uint, patch *Patch) (*Tariff, error) {
	if _, err := s.GetByID(ctx, id, parkingIDs); err != nil {
		return nil, err
	}

	if err := s.repository.Update(ctx, id, patch); err != nil {
		return nil, err
	}

	if err := s.cacheRepository.Invalidate(ctx, id); err != nil {
		errorreporter.CaptureSoft(ctx, err, nil)
	}

	return s.repository.GetByID(ctx, id, parkingIDs)
}

// DeactivateByParkingID Отключает тарифы отключённой парковки. Повтор безопасен: уже неактивные тарифы запрос не выбирает
func (s *Service) DeactivateByParkingID(ctx context.Context, parkingID uint) error {
	ids, err := s.repository.DeactivateByParkingID(ctx, parkingID)
	if err != nil {
		return err
	}

	for _, id := range ids {
		if err := s.cacheRepository.Invalidate(ctx, id); err != nil {
			errorreporter.CaptureSoft(ctx, err, map[string]string{"component": "tariff_cache"})
		}
	}

	return nil
}

// inParkings Парковка входит в скоуп; nil — без ограничения
func inParkings(parkingIDs []uint, parkingID uint) bool {
	return parkingIDs == nil || slices.Contains(parkingIDs, parkingID)
}
