package partner

import (
	"context"
	"time"
)

// NonceRepository Использованные nonce подписей; Remember — false, если nonce уже встречался за ttl (повтор запроса)
type NonceRepository interface {
	Remember(ctx context.Context, nonce string, ttl time.Duration) (bool, error)
}
