package console

import (
	postgresapp "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app"
	"github.com/spf13/cobra"
)

// newMigrateCommand Миграции схемы из internal/migrations; PostgresKernel их не катит
func newMigrateCommand(migrator *postgresapp.Migrator) *cobra.Command {
	cmd := &cobra.Command{
		Use:   "migrate",
		Short: "Миграции схемы БД",
	}

	cmd.AddCommand(
		&cobra.Command{
			Use:   "up",
			Short: "Применить все неприменённые миграции",
			Args:  cobra.NoArgs,
			RunE: func(_ *cobra.Command, _ []string) error {
				return migrator.Up()
			},
		},
		&cobra.Command{
			Use:   "down",
			Short: "Откатить последнюю применённую миграцию",
			Args:  cobra.NoArgs,
			RunE: func(_ *cobra.Command, _ []string) error {
				return migrator.RollbackLast()
			},
		},
		&cobra.Command{
			Use:   "down-to <id>",
			Short: "Откатить миграции после <id>; сама <id> остаётся",
			Args:  cobra.ExactArgs(1),
			RunE: func(_ *cobra.Command, args []string) error {
				return migrator.RollbackTo(args[0])
			},
		},
	)

	return cmd
}
