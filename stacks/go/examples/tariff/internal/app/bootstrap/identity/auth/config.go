package auth

import (
	"fmt"
	"time"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
	integrationmiddleware "example.com/parking-service/internal/entrypoints/integration/http/middleware"
)

// adminAuthConfig JWT администраторов от сервиса авторизации; ключ, guard и TTL кеша обязательны, разбег часов — 0 без допуска
type adminAuthConfig struct {
	// JWTPublicKeyBase64 Публичный RSA-ключ (PEM в base64), которым сервис авторизации подписывает JWT (RS256)
	JWTPublicKeyBase64 string `mapstructure:"JWT_PUBLIC_KEY_BASE64" json:"JWT_PUBLIC_KEY_BASE64"`
	// JWTGuardName Принимаемый claim guardName; токены других guard'ов не принимаются
	JWTGuardName     string `mapstructure:"JWT_GUARD_NAME" json:"JWT_GUARD_NAME"`
	JWTLeewaySeconds uint   `mapstructure:"JWT_LEEWAY_SECONDS" json:"JWT_LEEWAY_SECONDS"`
	// CacheTTLSeconds Сколько администратор с правами живёт в кеше: столько же действует отзыв прав
	CacheTTLSeconds uint `mapstructure:"ADMIN_AUTH_CACHE_TTL_SECONDS" json:"ADMIN_AUTH_CACHE_TTL_SECONDS"`
}

func loadAdminAuthConfig() (*adminAuthConfig, error) {
	cfg := &adminAuthConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return nil, fmt.Errorf("admin auth config: %w", err)
	}

	if cfg.JWTPublicKeyBase64 == "" {
		return nil, fmt.Errorf("admin auth config: JWT_PUBLIC_KEY_BASE64 is required")
	}

	if cfg.JWTGuardName == "" {
		return nil, fmt.Errorf("admin auth config: JWT_GUARD_NAME is required")
	}

	if cfg.CacheTTLSeconds == 0 {
		return nil, fmt.Errorf("admin auth config: ADMIN_AUTH_CACHE_TTL_SECONDS is required")
	}

	return cfg, nil
}

func (c *adminAuthConfig) leeway() time.Duration {
	return time.Duration(c.JWTLeewaySeconds) * time.Second
}

func (c *adminAuthConfig) settings() admindomain.AuthSettings {
	return admindomain.AuthSettings{
		GuardName: c.JWTGuardName,
		CacheTTL:  time.Duration(c.CacheTTLSeconds) * time.Second,
	}
}

// partnerAuthConfig Машинный вызов партнёра: все значения обязательны, nonce живёт не меньше двух окон подписи
type partnerAuthConfig struct {
	APIKey                    string `mapstructure:"PARTNER_API_KEY" json:"PARTNER_API_KEY"`
	SignatureSecret           string `mapstructure:"PARTNER_SIGNATURE_SECRET" json:"PARTNER_SIGNATURE_SECRET"`
	SignatureToleranceSeconds uint   `mapstructure:"PARTNER_SIGNATURE_TOLERANCE_SECONDS" json:"PARTNER_SIGNATURE_TOLERANCE_SECONDS"`
	NonceTTLSeconds           uint   `mapstructure:"PARTNER_SIGNATURE_NONCE_TTL_SECONDS" json:"PARTNER_SIGNATURE_NONCE_TTL_SECONDS"`
}

func loadPartnerAuthConfig() (*partnerAuthConfig, error) {
	cfg := &partnerAuthConfig{}

	if err := config.InitConfig(cfg); err != nil {
		return nil, fmt.Errorf("partner auth config: %w", err)
	}

	if cfg.APIKey == "" {
		return nil, fmt.Errorf("partner auth config: PARTNER_API_KEY is required")
	}

	if cfg.SignatureSecret == "" {
		return nil, fmt.Errorf("partner auth config: PARTNER_SIGNATURE_SECRET is required")
	}

	if cfg.SignatureToleranceSeconds == 0 {
		return nil, fmt.Errorf("partner auth config: PARTNER_SIGNATURE_TOLERANCE_SECONDS is required")
	}

	// подпись принимается в окне ±tolerance: nonce, забытый раньше 2×tolerance, можно повторить
	if cfg.NonceTTLSeconds < 2*cfg.SignatureToleranceSeconds {
		return nil, fmt.Errorf("partner auth config: PARTNER_SIGNATURE_NONCE_TTL_SECONDS must be at least 2×PARTNER_SIGNATURE_TOLERANCE_SECONDS")
	}

	return cfg, nil
}

func (c *partnerAuthConfig) settings() partnerdomain.AuthSettings {
	return partnerdomain.AuthSettings{
		APIKey:   c.APIKey,
		NonceTTL: time.Duration(c.NonceTTLSeconds) * time.Second,
	}
}

func (c *partnerAuthConfig) signatureSettings() *integrationmiddleware.SignatureSettings {
	return &integrationmiddleware.SignatureSettings{
		Secret:    c.SignatureSecret,
		Tolerance: time.Duration(c.SignatureToleranceSeconds) * time.Second,
	}
}
