package tariff

import (
	"fmt"
	"net/url"
	"strings"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config"
)

// integrationsConfig Внешние системы модуля тарифов
type integrationsConfig struct {
	CityParkingBaseURL           string `mapstructure:"CITYPARKING_BASE_URL" json:"CITYPARKING_BASE_URL"`
	CityParkingTimeoutSeconds    uint   `mapstructure:"CITYPARKING_TIMEOUT_SECONDS" json:"CITYPARKING_TIMEOUT_SECONDS"`
	PartnerBillingBaseURL        string `mapstructure:"PARTNERBILLING_BASE_URL" json:"PARTNERBILLING_BASE_URL"`
	PartnerBillingTimeoutSeconds uint   `mapstructure:"PARTNERBILLING_TIMEOUT_SECONDS" json:"PARTNERBILLING_TIMEOUT_SECONDS"`
}

// loadIntegrationsConfig Обязательные адреса и таймауты; без них модуль не стартует. Адреса — без хвостового «/»
func loadIntegrationsConfig() (*integrationsConfig, error) {
	cfg := &integrationsConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return nil, fmt.Errorf("tariff integrations config: %w", err)
	}

	cityParkingBaseURL, err := normalizeBaseURL("CITYPARKING_BASE_URL", cfg.CityParkingBaseURL)
	if err != nil {
		return nil, err
	}

	partnerBillingBaseURL, err := normalizeBaseURL("PARTNERBILLING_BASE_URL", cfg.PartnerBillingBaseURL)
	if err != nil {
		return nil, err
	}

	if cfg.CityParkingTimeoutSeconds == 0 {
		return nil, fmt.Errorf("tariff integrations config: CITYPARKING_TIMEOUT_SECONDS is required")
	}

	if cfg.PartnerBillingTimeoutSeconds == 0 {
		return nil, fmt.Errorf("tariff integrations config: PARTNERBILLING_TIMEOUT_SECONDS is required")
	}

	cfg.CityParkingBaseURL = cityParkingBaseURL
	cfg.PartnerBillingBaseURL = partnerBillingBaseURL

	return cfg, nil
}

func (c *integrationsConfig) cityParkingTimeout() time.Duration {
	return time.Duration(c.CityParkingTimeoutSeconds) * time.Second
}

func (c *integrationsConfig) partnerBillingTimeout() time.Duration {
	return time.Duration(c.PartnerBillingTimeoutSeconds) * time.Second
}

func normalizeBaseURL(name string, value string) (string, error) {
	parsed, err := url.Parse(value)
	if err != nil || parsed.Host == "" || (parsed.Scheme != "http" && parsed.Scheme != "https") {
		return "", fmt.Errorf("tariff integrations config: %s must be an absolute http(s) URL, got %q", name, value)
	}

	return strings.TrimSuffix(value, "/"), nil
}

// consumerRetryConfig Повтор события handbook.parking.deactivated через retry-очередь; оба значения обязательны
type consumerRetryConfig struct {
	// MaxAttempts Столько доставок с временной ошибкой, после последней — фиксация в трекере и ACK
	MaxAttempts       uint `mapstructure:"TARIFF_PARKING_DEACTIVATED_MAX_ATTEMPTS" json:"TARIFF_PARKING_DEACTIVATED_MAX_ATTEMPTS"`
	RetryDelaySeconds uint `mapstructure:"TARIFF_PARKING_DEACTIVATED_RETRY_DELAY_SECONDS" json:"TARIFF_PARKING_DEACTIVATED_RETRY_DELAY_SECONDS"`
}

func loadConsumerRetryConfig() (*consumerRetryConfig, error) {
	cfg := &consumerRetryConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return nil, fmt.Errorf("tariff consumer retry config: %w", err)
	}

	if cfg.MaxAttempts == 0 {
		return nil, fmt.Errorf("tariff consumer retry config: TARIFF_PARKING_DEACTIVATED_MAX_ATTEMPTS is required")
	}

	if cfg.RetryDelaySeconds == 0 {
		return nil, fmt.Errorf("tariff consumer retry config: TARIFF_PARKING_DEACTIVATED_RETRY_DELAY_SECONDS is required")
	}

	return cfg, nil
}

func (c *consumerRetryConfig) retryDelay() time.Duration {
	return time.Duration(c.RetryDelaySeconds) * time.Second
}
