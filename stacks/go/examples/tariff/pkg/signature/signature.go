// Package signature HMAC-подпись машинного вызова: заголовок X-Signature
// t=<unix ts>,n=<nonce>,v1=<hex HMAC-SHA256(secret, "{t}\n{n}\n{METHOD}\n{path?query}\n{body}")>
package signature

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"errors"
	"fmt"
	"regexp"
	"strconv"
	"strings"
	"time"
)

const (
	// HeaderName Заголовок с подписью
	HeaderName = "X-Signature"
	// Version Версия схемы подписи — ключ значения подписи в заголовке
	Version = "v1"
)

var (
	ErrMalformed = errors.New("signature: missing or malformed header")
	ErrExpired   = errors.New("signature: timestamp out of window")
	ErrMismatch  = errors.New("signature: mismatch")
)

var (
	noncePattern     = regexp.MustCompile(`^[A-Za-z0-9\-]{8,64}$`)
	signaturePattern = regexp.MustCompile(`^[0-9a-fA-F]{64}$`)
)

// Parsed Разобранный заголовок подписи
type Parsed struct {
	Timestamp int64
	Nonce     string
	// Signature hex в нижнем регистре
	Signature string
}

// Canonical Строка, которую подписывает отправитель
func Canonical(timestamp int64, nonce, method, path string, body []byte) string {
	return fmt.Sprintf("%d\n%s\n%s\n%s\n%s", timestamp, nonce, strings.ToUpper(method), path, body)
}

// Sign hex HMAC-SHA256 канонической строки
func Sign(secret string, timestamp int64, nonce, method, path string, body []byte) string {
	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write([]byte(Canonical(timestamp, nonce, method, path, body)))

	return hex.EncodeToString(mac.Sum(nil))
}

// HeaderValue Значение заголовка для заданных времени и nonce
func HeaderValue(secret string, timestamp int64, nonce, method, path string, body []byte) string {
	return fmt.Sprintf("t=%d,n=%s,%s=%s", timestamp, nonce, Version, Sign(secret, timestamp, nonce, method, path, body))
}

// Parse Разбор заголовка; повтор ключа, лишний ключ, пустое значение или чужой алфавит поля — ErrMalformed
func Parse(header string) (Parsed, error) {
	if header == "" {
		return Parsed{}, ErrMalformed
	}

	parts := make(map[string]string, 3)

	for _, pair := range strings.Split(header, ",") {
		key, value, found := strings.Cut(strings.TrimSpace(pair), "=")
		if !found || value == "" {
			return Parsed{}, ErrMalformed
		}

		if _, known := parts[key]; known || (key != "t" && key != "n" && key != Version) {
			return Parsed{}, ErrMalformed
		}

		parts[key] = value
	}

	timestamp, err := strconv.ParseInt(parts["t"], 10, 64)
	if err != nil || timestamp < 0 || !noncePattern.MatchString(parts["n"]) || !signaturePattern.MatchString(parts[Version]) {
		return Parsed{}, ErrMalformed
	}

	return Parsed{Timestamp: timestamp, Nonce: parts["n"], Signature: strings.ToLower(parts[Version])}, nil
}

// Verify Время подписи в окне tolerance от now и подпись совпадает; сравнение — за постоянное время
func Verify(secret string, parsed Parsed, method, path string, body []byte, tolerance time.Duration, now time.Time) error {
	if now.Sub(time.Unix(parsed.Timestamp, 0)).Abs() > tolerance {
		return ErrExpired
	}

	expected := Sign(secret, parsed.Timestamp, parsed.Nonce, method, path, body)
	if !hmac.Equal([]byte(parsed.Signature), []byte(expected)) {
		return ErrMismatch
	}

	return nil
}
