package outbox_tariff_event

import (
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/logger"
	"github.com/spf13/cobra"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// NewRetryCommand Повтор зависших событий тарифов. Запускается по расписанию (cron); параллельный запуск безопасен —
// выборка пропускает строки, взятые другим прогоном. Прогон завершается, когда зависших событий нет
func NewRetryCommand(retryCommand *outboxdomain.RetryOutboxTariffEventsCommand) *cobra.Command {
	return &cobra.Command{
		Use:   "outbox-tariff-event:retry",
		Short: "Повторить отправку в RabbitMQ зависших событий тарифов",
		Args:  cobra.NoArgs,
		RunE: func(cmd *cobra.Command, _ []string) error {
			result, err := retryCommand.Exec(cmd.Context())
			if err != nil {
				return err
			}

			message := fmt.Sprintf("outbox tariff events: published=%d failed=%d", result.Published, result.Failed)
			logger.Info(cmd.Context(), message)

			return nil
		},
	}
}
