package admin

import (
	"context"
	"errors"
	"fmt"
	"time"

	"gorm.io/gorm"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

// buildingPermissionPrefix Право на здание — building.<key>; парковка здания — buildings.parking_id
const buildingPermissionPrefix = "building."

func NewPostgresRepository(client *gorm.DB, guardName string) *PostgresRepository {
	return &PostgresRepository{
		client:    client,
		guardName: guardName,
	}
}

// PostgresRepository Администраторы, их права и парковки из общей БД администраторов; guardName — права только этого guard'а
type PostgresRepository struct {
	client    *gorm.DB
	guardName string
}

func (r *PostgresRepository) GetByID(ctx context.Context, id uint) (*admindomain.Admin, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var model admin
	result := r.client.WithContext(ctx).
		Where("id = ?", id).
		First(&model)

	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return nil, nil
	}

	if result.Error != nil {
		return nil, fmt.Errorf("get admin failed (admin_id=%d): %w", id, result.Error)
	}

	return modelToEntity(&model)
}

func (r *PostgresRepository) GetPermissionNames(ctx context.Context, adminID uint) ([]string, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	var names []string
	err := r.client.WithContext(ctx).
		Table("public.admins_roles AS ar").
		Joins("JOIN public.role_has_permissions rhp ON rhp.role_id = ar.role_id").
		Joins("JOIN public.permissions p ON p.id = rhp.permission_id").
		Where("ar.admin_id = ? AND p.guard_name = ?", adminID, r.guardName).
		Distinct("p.name").
		Pluck("p.name", &names).Error
	if err != nil {
		return nil, fmt.Errorf("get admin permissions failed (admin_id=%d): %w", adminID, err)
	}

	return names, nil
}

func (r *PostgresRepository) GetAllowedParkingIDs(ctx context.Context, adminID uint) ([]uint, error) {
	ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()

	permissionNames := r.client.WithContext(ctx).
		Table("public.admins_roles AS ar").
		Joins("JOIN public.role_has_permissions rhp ON rhp.role_id = ar.role_id").
		Joins("JOIN public.permissions p ON p.id = rhp.permission_id").
		Where("ar.admin_id = ? AND p.guard_name = ?", adminID, r.guardName).
		Select("p.name")

	var ids []uint
	err := r.client.WithContext(ctx).
		Table("public.buildings AS b").
		Where("b.parking_id IS NOT NULL").
		Where("(? || b.key) IN (?)", buildingPermissionPrefix, permissionNames).
		Distinct("b.parking_id").
		Pluck("b.parking_id", &ids).Error
	if err != nil {
		return nil, fmt.Errorf("get admin parkings failed (admin_id=%d): %w", adminID, err)
	}

	return ids, nil
}
