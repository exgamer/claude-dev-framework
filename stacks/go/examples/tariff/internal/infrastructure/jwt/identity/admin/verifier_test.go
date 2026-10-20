package admin

import (
	"crypto/rand"
	"crypto/rsa"
	"encoding/base64"
	"os"
	"testing"
	"time"

	"github.com/golang-jwt/jwt/v5"
)

// testdata/jwt_test.{key,pub} — ключи только для тестов, сгенерированы openssl genpkey -algorithm RSA
func loadTestKeys(t *testing.T) (*rsa.PrivateKey, string) {
	t.Helper()

	privatePEM, err := os.ReadFile("testdata/jwt_test.key")
	if err != nil {
		t.Fatal(err)
	}

	privateKey, err := jwt.ParseRSAPrivateKeyFromPEM(privatePEM)
	if err != nil {
		t.Fatal(err)
	}

	publicPEM, err := os.ReadFile("testdata/jwt_test.pub")
	if err != nil {
		t.Fatal(err)
	}

	return privateKey, base64.StdEncoding.EncodeToString(publicPEM)
}

func sign(t *testing.T, key *rsa.PrivateKey, claims jwt.MapClaims) string {
	t.Helper()

	token, err := jwt.NewWithClaims(jwt.SigningMethodRS256, claims).SignedString(key)
	if err != nil {
		t.Fatal(err)
	}

	return token
}

func baseClaims(sub any) jwt.MapClaims {
	now := time.Now()

	return jwt.MapClaims{
		"sub":       sub,
		"iat":       now.Unix(),
		"nbf":       now.Unix(),
		"exp":       now.Add(time.Hour).Unix(),
		"guardName": "admin-api",
	}
}

func TestVerifyAcceptsNumericAndStringSub(t *testing.T) {
	privateKey, publicKey := loadTestKeys(t)

	verifier, err := NewVerifier(publicKey, 30*time.Second)
	if err != nil {
		t.Fatal(err)
	}

	for name, sub := range map[string]any{"numeric": 42, "string": "42"} {
		got, err := verifier.Verify(sign(t, privateKey, baseClaims(sub)))
		if err != nil {
			t.Fatalf("%s: unexpected error: %v", name, err)
		}

		if got.AdminID != 42 || got.GuardName != "admin-api" {
			t.Fatalf("%s: unexpected claims %+v", name, got)
		}
	}
}

func TestVerifyRejectsBadTokens(t *testing.T) {
	privateKey, publicKey := loadTestKeys(t)

	verifier, err := NewVerifier(publicKey, 0)
	if err != nil {
		t.Fatal(err)
	}

	otherKey, err := rsa.GenerateKey(rand.Reader, 2048)
	if err != nil {
		t.Fatal(err)
	}

	expired := baseClaims(1)
	expired["exp"] = time.Now().Add(-time.Minute).Unix()

	noExp := baseClaims(1)
	delete(noExp, "exp")

	hs256, err := jwt.NewWithClaims(jwt.SigningMethodHS256, baseClaims(1)).SignedString([]byte("shared-secret"))
	if err != nil {
		t.Fatal(err)
	}

	none, err := jwt.NewWithClaims(jwt.SigningMethodNone, baseClaims(1)).SignedString(jwt.UnsafeAllowNoneSignatureType)
	if err != nil {
		t.Fatal(err)
	}

	cases := map[string]string{
		"expired":     sign(t, privateKey, expired),
		"no exp":      sign(t, privateKey, noExp),
		"sub 0":       sign(t, privateKey, baseClaims(0)),
		"sub not int": sign(t, privateKey, baseClaims("admin")),
		"foreign key": sign(t, otherKey, baseClaims(1)),
		"hs256 alg":   hs256,
		"none alg":    none,
		"garbage":     "not.a.jwt",
	}

	for name, token := range cases {
		if _, err := verifier.Verify(token); err == nil {
			t.Fatalf("%s: expected error", name)
		}
	}
}

func TestNewVerifierRejectsBadKey(t *testing.T) {
	for name, key := range map[string]string{
		"not base64": "%%%",
		"not pem":    base64.StdEncoding.EncodeToString([]byte("plain text")),
	} {
		if _, err := NewVerifier(key, 0); err == nil {
			t.Fatalf("%s: expected error", name)
		}
	}
}
