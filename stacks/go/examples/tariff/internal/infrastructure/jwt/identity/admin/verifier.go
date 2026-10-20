package admin

import (
	"crypto/rsa"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"strconv"
	"time"

	"github.com/golang-jwt/jwt/v5"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
)

// tokenClaims Claims токена сервиса авторизации: sub — числом или строкой, guardName — guard, которым выпущен токен
type tokenClaims struct {
	jwt.RegisteredClaims
	Sub       json.Number `json:"sub"`
	GuardName string      `json:"guardName"`
}

// NewVerifier Публичный RSA-ключ — PEM в base64, как он лежит в env
func NewVerifier(publicKeyBase64 string, leeway time.Duration) (*Verifier, error) {
	pem, err := base64.StdEncoding.DecodeString(publicKeyBase64)
	if err != nil {
		return nil, fmt.Errorf("jwt public key: base64: %w", err)
	}

	key, err := jwt.ParseRSAPublicKeyFromPEM(pem)
	if err != nil {
		return nil, fmt.Errorf("jwt public key: %w", err)
	}

	return &Verifier{key: key, leeway: leeway}, nil
}

// Verifier Проверка JWT по RS256: другой алгоритм (HS256, none) и токен без exp не принимаются
type Verifier struct {
	key    *rsa.PublicKey
	leeway time.Duration
}

func (v *Verifier) Verify(rawToken string) (*admindomain.TokenClaims, error) {
	claims := &tokenClaims{}

	_, err := jwt.ParseWithClaims(
		rawToken,
		claims,
		v.keyFunc,
		jwt.WithValidMethods([]string{jwt.SigningMethodRS256.Alg()}),
		jwt.WithExpirationRequired(),
		jwt.WithLeeway(v.leeway),
	)
	if err != nil {
		return nil, err
	}

	adminID, err := strconv.ParseUint(claims.Sub.String(), 10, 64)
	if err != nil || adminID == 0 {
		return nil, errors.New("jwt: sub is not a positive integer")
	}

	return &admindomain.TokenClaims{
		AdminID:   uint(adminID),
		GuardName: claims.GuardName,
		ExpiresAt: claims.ExpiresAt.Time,
	}, nil
}

func (v *Verifier) keyFunc(*jwt.Token) (any, error) {
	return v.key, nil
}
