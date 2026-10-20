package migrations

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration"
	"gorm.io/gorm"
)

// migration20261020100100 Outbox событий тарифов. Индексы — под внешние ключи и под выборку повтора (незавершённые статусы по updated_at)
func migration20261020100100() *migration.Migration {
	return &migration.Migration{
		ID: "20261020100100_create_outbox_tariff_events_table",
		Migrate: func(tx *gorm.DB) error {
			err := tx.Exec(`CREATE TABLE parking.outbox_tariff_events (
				id BIGSERIAL PRIMARY KEY,
				tariff_id BIGINT NOT NULL REFERENCES parking.tariffs (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
				parking_id BIGINT NOT NULL REFERENCES parking.parkings (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
				name VARCHAR(255) NOT NULL,
				currency CHAR(3) NOT NULL,
				grace_minutes INTEGER NOT NULL,
				price BIGINT NOT NULL,
				routing_key VARCHAR(64) NOT NULL,
				status SMALLINT NOT NULL,
				attempts INTEGER NOT NULL DEFAULT 0,
				published_at TIMESTAMPTZ NULL,
				created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
				updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
			)`).Error
			if err != nil {
				return err
			}

			err = tx.Exec(`CREATE INDEX outbox_tariff_events_tariff_id_idx ON parking.outbox_tariff_events (tariff_id)`).Error
			if err != nil {
				return err
			}

			err = tx.Exec(`CREATE INDEX outbox_tariff_events_parking_id_idx ON parking.outbox_tariff_events (parking_id)`).Error
			if err != nil {
				return err
			}

			return tx.Exec(`CREATE INDEX outbox_tariff_events_retry_idx ON parking.outbox_tariff_events (updated_at, id) WHERE status IN (1, 4)`).Error
		},
		Rollback: func(tx *gorm.DB) error {
			return tx.Exec(`DROP TABLE parking.outbox_tariff_events`).Error
		},
	}
}
