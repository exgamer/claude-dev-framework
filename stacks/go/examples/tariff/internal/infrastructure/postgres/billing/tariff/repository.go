package tariff

import (
	"context"
	"errors"
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/helpers"
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
	"github.com/jackc/pgx/v5/pgconn"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// defaultTariffUniqueIndex Частичный уникальный индекс «у парковки не больше одного тарифа по умолчанию» (миграция 20261020100200)
const defaultTariffUniqueIndex = "tariffs_parking_id_default_unique"

// uniqueViolationCode SQLSTATE нарушения уникальности Postgres
const uniqueViolationCode = "23505"

func NewPostgresRepository(client *gorm.DB) *PostgresRepository {
	return &PostgresRepository{
		client: client,
	}
}

type PostgresRepository struct {
	client *gorm.DB
}

// GetByID Скоуп без All и без IDs — ни одной парковки: nil, nil без запроса
func (r *PostgresRepository) GetByID(ctx context.Context, id uint, scope tariffdomain.ParkingScope) (*tariffdomain.Tariff, error) {
	if !scope.All && len(scope.IDs) == 0 {
		return nil, nil
	}

	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	query := r.client.WithContext(ctx).
		Where("id = ?", id).
		Where("deleted_at IS NULL")

	if !scope.All {
		query = query.Where("parking_id IN ?", scope.IDs)
	}

	var model tariff
	result := query.First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, fmt.Errorf("get tariff failed (tariff_id=%d): %w", id, result.Error)
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
		return nil, fmt.Errorf("get default tariff failed (parking_id=%d): %w", parkingID, result.Error)
	}

	return tariffToEntity(&model), nil
}

func (r *PostgresRepository) Paginated(ctx context.Context, search *tariffdomain.Search) (*pagination.Paginated[tariffdomain.Tariff], error) {
	// Таймаут — секунды числом: хелпер сам умножает на time.Second (gosdk-db-core v1.0.10, gorm_paginated_helper.go)
	helper := helpers.NewGormPaginatedHelper[tariffdomain.Tariff](ctx, r.client).SetTimeout(10)

	// per_page не передан — умолчание хелпера 30; SetPerPage(0) дал бы 20 из pagination.Pages
	if search.PerPage > 0 {
		helper.SetPerPage(search.PerPage)
	}

	paginated, err := helper.Paginated(search.Page, func(db *gorm.DB) *gorm.DB {
		query := db.Model(&tariff{}).Where("deleted_at IS NULL")

		// скоуп: All — без условия, иначе только свои; пустой IDs — ни одной записи
		switch {
		case search.Scope.All: // без условия
		case len(search.Scope.IDs) == 0:
			query = query.Where("FALSE")
		default:
			query = query.Where("parking_id IN ?", search.Scope.IDs)
		}

		if search.ParkingID != 0 {
			query = query.Where("parking_id = ?", search.ParkingID)
		}

		return query.Order("id DESC")
	})
	if err != nil {
		return nil, fmt.Errorf("paginate tariffs failed (parking_id=%d, page=%d): %w", search.ParkingID, search.Page, err)
	}

	return paginated, nil
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

	result := r.client.WithContext(ctx).
		Model(&tariff{}).
		Where("id = ?", id).
		Where("deleted_at IS NULL").
		Updates(columns)
	if result.Error != nil {
		return fmt.Errorf("update tariff failed (tariff_id=%d): %w", id, result.Error)
	}

	if result.RowsAffected == 0 {
		return fmt.Errorf("update tariff failed (tariff_id=%d): %w", id, tariffdomain.ErrTariffNotFound)
	}

	return nil
}

func (r *PostgresRepository) SetDefault(ctx context.Context, id uint, isDefault bool) error {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	result := r.client.WithContext(ctx).
		Model(&tariff{}).
		Where("id = ?", id).
		Where("deleted_at IS NULL").
		Update("is_default", isDefault)
	if result.Error != nil {
		return setDefaultError(result.Error, id)
	}

	if result.RowsAffected == 0 {
		return fmt.Errorf("set default tariff failed (tariff_id=%d): %w", id, tariffdomain.ErrTariffNotFound)
	}

	return nil
}

// setDefaultError Ошибка записи флага: нарушение индекса тарифа по умолчанию — tariffdomain.ErrDefaultTariffTaken, прочее — с ID тарифа
func setDefaultError(err error, id uint) error {
	if err == nil {
		return nil
	}

	if isUniqueViolation(err, defaultTariffUniqueIndex) {
		return fmt.Errorf("set default tariff failed (tariff_id=%d): %w", id, tariffdomain.ErrDefaultTariffTaken)
	}

	return fmt.Errorf("set default tariff failed (tariff_id=%d): %w", id, err)
}

// isUniqueViolation Нарушение именно индекса index: SQLSTATE 23505 и имя ограничения из ошибки драйвера; чужой индекс — не оно
func isUniqueViolation(err error, index string) bool {
	var pgErr *pgconn.PgError

	return errors.As(err, &pgErr) && pgErr.Code == uniqueViolationCode && pgErr.ConstraintName == index
}

func (r *PostgresRepository) DeactivateByParkingID(ctx context.Context, parkingID uint) ([]uint, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var models []tariff
	result := r.client.WithContext(ctx).
		Model(&models).
		Clauses(clause.Returning{Columns: []clause.Column{{Name: "id"}}}).
		Where("parking_id = ?", parkingID).
		Where("is_active = TRUE").
		Where("deleted_at IS NULL").
		Update("is_active", false)

	if result.Error != nil {
		return nil, fmt.Errorf("deactivate tariffs failed (parking_id=%d): %w", parkingID, result.Error)
	}

	ids := make([]uint, 0, len(models))

	for _, model := range models {
		ids = append(ids, model.ID)
	}

	return ids, nil
}
