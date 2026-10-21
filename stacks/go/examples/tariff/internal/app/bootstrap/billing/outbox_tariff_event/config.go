package outbox_tariff_event

import (
	"fmt"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config"
)

// maxAttemptsConfig Предел отправок события тарифа; владелец настройки — этот модуль: модуль тарифов (первая отправка)
// и консольный bootstrap outbox (повтор) берут значение через LoadMaxAttempts. Серверного module.go у outbox нет
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
