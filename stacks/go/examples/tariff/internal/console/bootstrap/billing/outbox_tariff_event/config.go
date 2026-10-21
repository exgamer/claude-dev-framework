package outbox_tariff_event

import (
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// retryConfig Повтор событий тарифов: все значения обязательны, без них модуль не стартует; предел отправок — у владельца
// настройки, outbox_tariff_event.LoadMaxAttempts серверного bootstrap
type retryConfig struct {
	BatchSize         uint `mapstructure:"OUTBOX_TARIFF_EVENT_BATCH_SIZE" json:"OUTBOX_TARIFF_EVENT_BATCH_SIZE"`
	StaleAfterSeconds uint `mapstructure:"OUTBOX_TARIFF_EVENT_STALE_AFTER_SECONDS" json:"OUTBOX_TARIFF_EVENT_STALE_AFTER_SECONDS"`
}

func loadRetryConfig() (*retryConfig, error) {
	cfg := &retryConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return nil, fmt.Errorf("outbox tariff event config: %w", err)
	}

	if cfg.BatchSize == 0 {
		return nil, fmt.Errorf("outbox tariff event config: OUTBOX_TARIFF_EVENT_BATCH_SIZE is required")
	}

	if cfg.StaleAfterSeconds == 0 {
		return nil, fmt.Errorf("outbox tariff event config: OUTBOX_TARIFF_EVENT_STALE_AFTER_SECONDS is required")
	}

	return cfg, nil
}

func (c *retryConfig) settings() outboxdomain.RetrySettings {
	return outboxdomain.RetrySettings{
		BatchSize:  c.BatchSize,
		StaleAfter: time.Duration(c.StaleAfterSeconds) * time.Second,
	}
}
