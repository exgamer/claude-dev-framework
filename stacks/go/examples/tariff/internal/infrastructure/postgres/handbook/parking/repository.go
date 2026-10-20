package parking

import (
	"context"
	"errors"
	"fmt"
	"time"

	"gorm.io/gorm"

	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

func NewPostgresRepository(client *gorm.DB) *PostgresRepository {
	return &PostgresRepository{
		client: client,
	}
}

type PostgresRepository struct {
	client *gorm.DB
}

func (r *PostgresRepository) GetByID(ctx context.Context, id uint) (*parkingdomain.Parking, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var model parking
	result := r.client.WithContext(ctx).
		Where("id = ?", id).
		Where("deleted_at IS NULL").
		First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, fmt.Errorf("get parking failed (parking_id=%d): %w", id, result.Error)
	}

	return modelToEntity(&model), nil
}
