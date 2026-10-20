package migrations

import (
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration"
	"gorm.io/gorm"
)

// migration20261020100200 У парковки не больше одного тарифа по умолчанию: частичный уникальный индекс.
// Дубли молча не исправляются: какой тариф оставить по умолчанию — решение владельца данных, а снятый флаг не восстановить откатом.
// Нашлись — миграция падает со списком парковок, данные правятся отдельной миграцией, затем эта применяется повторно
func migration20261020100200() *migration.Migration {
	return &migration.Migration{
		ID: "20261020100200_add_default_tariff_unique_index_to_tariffs_table",
		Migrate: func(tx *gorm.DB) error {
			var parkingIDs []uint

			err := tx.Raw(`SELECT parking_id FROM parking.tariffs
				WHERE is_default = TRUE AND deleted_at IS NULL
				GROUP BY parking_id
				HAVING COUNT(*) > 1
				ORDER BY parking_id
				LIMIT 100`).
				Scan(&parkingIDs).
				Error
			if err != nil {
				return fmt.Errorf("check duplicate default tariffs failed: %w", err)
			}

			if len(parkingIDs) > 0 {
				return fmt.Errorf("у парковок больше одного тарифа по умолчанию, индекс не создан: исправить данные отдельной миграцией и повторить (parking_id=%v, первые 100)", parkingIDs)
			}

			return tx.Exec(`CREATE UNIQUE INDEX tariffs_parking_id_default_unique ON parking.tariffs (parking_id) WHERE is_default = TRUE AND deleted_at IS NULL`).Error
		},
		Rollback: func(tx *gorm.DB) error {
			return tx.Exec(`DROP INDEX parking.tariffs_parking_id_default_unique`).Error
		},
	}
}
