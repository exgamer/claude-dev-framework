package migrations

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration"
	"gorm.io/gorm"
)

// migration20261020100000 Существующие тарифы активны: NOT NULL с DEFAULT TRUE, без отдельного backfill
func migration20261020100000() *migration.Migration {
	return &migration.Migration{
		ID: "20261020100000_add_is_active_to_tariffs_table",
		Migrate: func(tx *gorm.DB) error {
			return tx.Exec(`ALTER TABLE parking.tariffs ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE`).Error
		},
		Rollback: func(tx *gorm.DB) error {
			return tx.Exec(`ALTER TABLE parking.tariffs DROP COLUMN is_active`).Error
		},
	}
}
