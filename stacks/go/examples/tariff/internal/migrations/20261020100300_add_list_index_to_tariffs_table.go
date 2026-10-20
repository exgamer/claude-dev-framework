package migrations

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration"
	"gorm.io/gorm"
)

// migration20261020100300 Индекс под список тарифов (parking_id IN …, ORDER BY id DESC) и отключение тарифов парковки (parking_id = …).
// CONCURRENTLY — таблица уже в работе, запись не блокируется; возможно, потому что Migrator катит миграции без транзакции (db-core Run).
// Сбой построения оставляет индекс INVALID — перед повтором Rollback (DROP INDEX)
func migration20261020100300() *migration.Migration {
	return &migration.Migration{
		ID: "20261020100300_add_list_index_to_tariffs_table",
		Migrate: func(tx *gorm.DB) error {
			return tx.Exec(`CREATE INDEX CONCURRENTLY tariffs_parking_id_id_idx ON parking.tariffs (parking_id, id DESC) WHERE deleted_at IS NULL`).Error
		},
		Rollback: func(tx *gorm.DB) error {
			return tx.Exec(`DROP INDEX CONCURRENTLY IF EXISTS parking.tariffs_parking_id_id_idx`).Error
		},
	}
}
