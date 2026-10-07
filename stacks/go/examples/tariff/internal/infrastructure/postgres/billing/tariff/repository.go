package tariff

import (
	"context"
	"errors"
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/helpers"
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
	"gorm.io/gorm"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func NewPostgresRepository(client *gorm.DB) *PostgresRepository {
	return &PostgresRepository{
		client: client,
	}
}

type PostgresRepository struct {
	client *gorm.DB
}

func (r *PostgresRepository) GetByID(ctx context.Context, id uint) (*tariffdomain.Tariff, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var model tariff
	result := r.client.WithContext(ctx).
		Where("id = ?", id).
		Where("deleted_at IS NULL").
		First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, result.Error
	}

	return tariffToEntity(&model), nil
}

func (r *PostgresRepository) GetDefaultByParkingID(ctx context.Context, parkingID uint) (*tariffdomain.Tariff, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var model tariff
	result := r.client.WithContext(ctx).
		Where("parking_id = ?", parkingID).
		Where("is_default = TRUE").
		Where("deleted_at IS NULL").
		First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, result.Error
	}

	return tariffToEntity(&model), nil
}

func (r *PostgresRepository) Paginated(ctx context.Context, search *tariffdomain.Search) (*pagination.Paginated[tariffdomain.Tariff], error) {
	return helpers.NewGormPaginatedHelper[tariffdomain.Tariff](ctx, r.client).
		SetTimeout(10*time.Second).
		SetPerPage(search.PerPage).
		Paginated(search.Page, func(db *gorm.DB) *gorm.DB {
			query := db.Model(&tariff{}).Where("deleted_at IS NULL")

			if search.ParkingID != 0 {
				query = query.Where("parking_id = ?", search.ParkingID)
			}

			return query.Order("id DESC")
		})
}

func (r *PostgresRepository) Create(ctx context.Context, entity *tariffdomain.Tariff) (*tariffdomain.Tariff, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	model := tariffFromEntity(entity)

	if err := r.client.WithContext(ctx).Create(model).Error; err != nil {
		return nil, fmt.Errorf("create tariff failed (parking_id=%d): %w", entity.ParkingID, err)
	}

	return tariffToEntity(model), nil
}

func (r *PostgresRepository) Update(ctx context.Context, id uint, patch *tariffdomain.Patch) error {
	columns := patchToColumns(patch)

	if len(columns) == 0 {
		return nil
	}

	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	return r.client.WithContext(ctx).
		Model(&tariff{}).
		Where("id = ?", id).
		Updates(columns).
		Error
}

func (r *PostgresRepository) SetDefault(ctx context.Context, id uint, isDefault bool) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	return r.client.WithContext(ctx).
		Model(&tariff{}).
		Where("id = ?", id).
		Update("is_default", isDefault).
		Error
}
