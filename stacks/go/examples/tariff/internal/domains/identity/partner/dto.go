package partner

import "time"

// AuthSettings Настройки аутентификации партнёра из конфига; секрет и окно подписи — у middleware подписи
type AuthSettings struct {
	// APIKey Ключ партнёра в заголовке Api-Key
	APIKey string
	// NonceTTL Сколько помнится nonce подписи; не меньше двух окон подписи, иначе повтор в окне пройдёт
	NonceTTL time.Duration
}
