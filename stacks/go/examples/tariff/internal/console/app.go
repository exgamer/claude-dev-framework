package console

import (
	"context"
	"log"
	"time"

	consoleapp "git.mpinnovations.kz/mps/go-packages/gosdk-console-core/pkg/app"
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	postgresapp "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app"
	postgresDI "git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di"
	rabbitapp "git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/app"
	sentryapp "git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app"

	"example.com/parking-service/internal/app/bootstrap/billing/outbox_tariff_event"
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

	consoleKernel.AddCommand(newMigrateCommand(postgresapp.NewMigrator(postgresClient, migrations.All()...)))

	err = appInstance.RegisterAndInitModules(
		&outbox_tariff_event.Module{Console: consoleKernel},
	)
	if err != nil {
		appInstance.Close()

		return nil, err
	}

	return appInstance, nil
}

// Close Закрывает соединения ядер в обратном порядке; ошибка закрытия — только в лог, итог команды не меняет
func (a *App) Close() {
	ctx, cancel := context.WithTimeout(context.Background(), closeTimeout)
	defer cancel()

	for i := len(a.closers) - 1; i >= 0; i-- {
		if err := a.closers[i].Stop(ctx); err != nil {
			log.Printf("close %s: %v", a.closers[i].Name(), err)
		}
	}
}
