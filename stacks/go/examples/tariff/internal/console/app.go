package console

import (
	"context"
	"fmt"
	"time"

	consoleapp "git.mpinnovations.kz/mps/go-packages/gosdk-console-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/logger"
	postgresapp "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	rabbitapp "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/app"
	sentryapp "git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app"

	"example.com/parking-service/internal/console/bootstrap/billing/outbox_tariff_event"
	"example.com/parking-service/internal/migrations"
)

// closeTimeout Предел закрытия соединений после команды
const closeTimeout = 10 * time.Second

// App Консольное приложение: своё соединение с БД и брокером, команды — в ConsoleKernel
type App struct {
	*app.App
	// closers Ядра, которые закрывает Close; WaitForShutdown у разовой команды не вызывается
	closers []app.KernelInterface
}

// closersOf Sentry — первым: Close идёт в обратном порядке, и Flush событий (в том числе ошибок закрытия) — последним
func closersOf(sentryKernel, postgresKernel, rabbitKernel app.KernelInterface) []app.KernelInterface {
	return []app.KernelInterface{sentryKernel, postgresKernel, rabbitKernel}
}

func NewApp() (*App, error) {
	sentryKernel := &sentryapp.SentryKernel{}
	postgresKernel := &postgresapp.PostgresKernel{}
	rabbitKernel := rabbitapp.NewRabbitKernel().EnablePublisher()
	consoleKernel := consoleapp.NewConsoleKernel("console")

	appInstance := &App{
		App:     app.NewApp(),
		closers: closersOf(sentryKernel, postgresKernel, rabbitKernel),
	}

	err := appInstance.RegisterAndInitKernels(
		sentryKernel,
		postgresKernel,
		rabbitKernel,
		consoleKernel,
	)
	if err != nil {
		appInstance.Close()

		return nil, err
	}

	postgresClient, err := postgresDI.GetDefaultPostgresConnection(appInstance.Container)
	if err != nil {
		appInstance.Close()

		return nil, err
	}

	migrator := postgresapp.NewMigrator(postgresClient, migrations.All()...)
	consoleKernel.AddCommand(newMigrateCommand(migrator))

	err = appInstance.RegisterAndInitModules(
		&outbox_tariff_event.Module{Console: consoleKernel},
	)
	if err != nil {
		appInstance.Close()

		return nil, err
	}

	return appInstance, nil
}

// Close Закрывает соединения ядер в обратном порядке; ошибка закрытия — в трекер и лог, итог команды не меняет
func (a *App) Close() {
	ctx, cancel := context.WithTimeout(context.Background(), closeTimeout)
	defer cancel()

	for i := len(a.closers) - 1; i >= 0; i-- {
		closer := a.closers[i]

		if err := closer.Stop(ctx); err != nil {
			closeErr := fmt.Errorf("close %s kernel failed: %w", closer.Name(), err)
			errorreporter.CaptureSoft(ctx, closeErr, map[string]string{"component": "console"})
			logger.Error(ctx, closeErr.Error())
		}
	}
}
