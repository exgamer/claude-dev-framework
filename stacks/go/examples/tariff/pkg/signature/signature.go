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

// Canonical Строка, которую подписывает отправитель
func Canonical(request Request) string {
	message := request.Message
	method := strings.ToUpper(message.Method)

	return fmt.Sprintf("%d\n%s\n%s\n%s\n%s", request.Timestamp, request.Nonce, method, message.Path, message.Body)
}

// Sign hex HMAC-SHA256 канонической строки
func Sign(secret string, request Request) string {
	canonical := Canonical(request)
	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write([]byte(canonical))

	return hex.EncodeToString(mac.Sum(nil))
}

// HeaderValue Значение заголовка для заданных времени и nonce
func HeaderValue(secret string, request Request) string {
	sum := Sign(secret, request)

	return fmt.Sprintf("t=%d,n=%s,%s=%s", request.Timestamp, request.Nonce, Version, sum)
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

// Verify Время подписи в окне Tolerance от Now и подпись совпадает; сравнение — за постоянное время
func Verify(params VerifyParams) error {
	signedAt := time.Unix(params.Parsed.Timestamp, 0)
	age := params.Now.Sub(signedAt)

	if age.Abs() > params.Tolerance {
		return ErrExpired
	}

	expected := Sign(params.Secret, Request{
		Timestamp: params.Parsed.Timestamp,
		Nonce:     params.Parsed.Nonce,
		Message:   params.Message,
	})
	if !hmac.Equal([]byte(params.Parsed.Signature), []byte(expected)) {
		return ErrMismatch
	}

	return nil
}
