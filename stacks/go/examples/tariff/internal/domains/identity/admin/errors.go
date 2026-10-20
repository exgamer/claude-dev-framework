package admin

import "errors"

var (
	// ErrInvalidToken Подпись или стандартные claims токена не прошли проверку
	ErrInvalidToken = errors.New("invalid token")
	// ErrUnauthorized Токен валиден, но не принимается: чужой guard, администратор не найден или отозван
	ErrUnauthorized = errors.New("unauthorized")
)
