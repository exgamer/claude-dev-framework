package migrations

import "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration"

// All Миграции в порядке применения
func All() []*migration.Migration {
	return []*migration.Migration{
		migration20261020100000(),
		migration20261020100100(),
		migration20261020100200(),
		migration20261020100300(),
	}
}
