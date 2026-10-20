package admin

import "time"

// TokenClaims Проверенные claims JWT сервиса авторизации
type TokenClaims struct {
	AdminID uint
	// GuardName Guard, которым выпущен токен; принимается только guard из AuthSettings
	GuardName string
	ExpiresAt time.Time
}

// AuthSettings Настройки аутентификации из конфига
type AuthSettings struct {
	// GuardName Принимаемый guard токена (admin-api)
	GuardName string
	// CacheTTL Сколько администратор с правами живёт в кеше
	CacheTTL time.Duration
}
