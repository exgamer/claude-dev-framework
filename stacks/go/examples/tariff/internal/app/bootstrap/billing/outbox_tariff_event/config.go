package outbox_tariff_event

import (
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config"

	outboxdomain "example.com/parking-service/internal/domains/billing/outbox_tariff_event"
)

// maxAttemptsConfig Предел отправок события тарифа; владелец настройки — этот модуль, модуль тарифов берёт значение через LoadMaxAttempts
type maxAttemptsConfig struct {
	// MaxAttempts После стольких неудачных отправок событие — failed (ручной разбор)
	MaxAttempts uint `mapstructure:"OUTBOX_TARIFF_EVENT_MAX_ATTEMPTS" json:"OUTBOX_TARIFF_EVENT_MAX_ATTEMPTS"`
}

// LoadMaxAttempts Обязательный OUTBOX_TARIFF_EVENT_MAX_ATTEMPTS: одно чтение для сервиса (отправка после коммита) и консоли (повтор)
func LoadMaxAttempts() (uint, error) {
	cfg := &maxAttemptsConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return 0, fmt.Errorf("outbox tariff event config: %w", err)
	}

	if cfg.MaxAttempts == 0 {
		return 0, fmt.Errorf("outbox tariff event config: OUTBOX_TARIFF_EVENT_MAX_ATTEMPTS is required")
	}

	return cfg.MaxAttempts, nil
}

// retryConfig Повтор событий тарифов: все значения обязательны, без них модуль не стартует; предел отправок — LoadMaxAttempts
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
