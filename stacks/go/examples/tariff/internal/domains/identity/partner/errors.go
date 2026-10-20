package partner

import "errors"

var (
	// ErrInvalidAPIKey Ключ не передан или не совпал
	ErrInvalidAPIKey = errors.New("invalid api key")
	// ErrNonceReplayed nonce подписи уже встречался — повтор запроса
	ErrNonceReplayed = errors.New("signature nonce replayed")
)
