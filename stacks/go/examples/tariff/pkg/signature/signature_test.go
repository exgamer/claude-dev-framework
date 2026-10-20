package signature

import (
	"errors"
	"testing"
	"time"
)

// Вектор посчитан независимо: printf '...' | openssl dgst -sha256 -hmac vector-secret
const (
	vectorSecret    = "vector-secret"
	vectorTimestamp = int64(1788150000)
	vectorNonce     = "11111111-2222-3333-4444-555555555555"
	vectorPath      = "/api/v1/integration/tariffs/42"
	vectorBody      = `{"a":1}`
	vectorHex       = "aef0607db22c169c20df74d33bf170e8ff20b1cdc9238e35391e1a3dfcf9925e"
)

func TestSignMatchesVector(t *testing.T) {
	got := Sign(vectorSecret, vectorTimestamp, vectorNonce, "post", vectorPath, []byte(vectorBody))
	if got != vectorHex {
		t.Fatalf("got %s, want %s", got, vectorHex)
	}
}

func TestParseAndVerifyRoundTrip(t *testing.T) {
	header := HeaderValue(vectorSecret, vectorTimestamp, vectorNonce, "POST", vectorPath, []byte(vectorBody))

	parsed, err := Parse(header)
	if err != nil {
		t.Fatal(err)
	}

	now := time.Unix(vectorTimestamp+10, 0)
	if err := Verify(vectorSecret, parsed, "POST", vectorPath, []byte(vectorBody), time.Minute, now); err != nil {
		t.Fatal(err)
	}
}

func TestVerifyRejects(t *testing.T) {
	parsed, err := Parse(HeaderValue(vectorSecret, vectorTimestamp, vectorNonce, "POST", vectorPath, []byte(vectorBody)))
	if err != nil {
		t.Fatal(err)
	}

	inWindow := time.Unix(vectorTimestamp, 0)

	cases := map[string]struct {
		secret, method, path, body string
		now                        time.Time
		want                       error
	}{
		"other secret": {"other", "POST", vectorPath, vectorBody, inWindow, ErrMismatch},
		"other path":   {vectorSecret, "POST", "/api/v1/integration/tariffs/43", vectorBody, inWindow, ErrMismatch},
		"other method": {vectorSecret, "GET", vectorPath, vectorBody, inWindow, ErrMismatch},
		"other body":   {vectorSecret, "POST", vectorPath, `{"a":2}`, inWindow, ErrMismatch},
		"too old":      {vectorSecret, "POST", vectorPath, vectorBody, inWindow.Add(2 * time.Minute), ErrExpired},
		"from future":  {vectorSecret, "POST", vectorPath, vectorBody, inWindow.Add(-2 * time.Minute), ErrExpired},
	}

	for name, tc := range cases {
		err := Verify(tc.secret, parsed, tc.method, tc.path, []byte(tc.body), time.Minute, tc.now)
		if !errors.Is(err, tc.want) {
			t.Fatalf("%s: got %v, want %v", name, err, tc.want)
		}
	}
}

func TestParseRejectsMalformed(t *testing.T) {
	cases := map[string]string{
		"empty":          "",
		"no signature":   "t=1788150000,n=11111111-2222",
		"short nonce":    "t=1788150000,n=abc,v1=" + vectorHex,
		"bad timestamp":  "t=abc,n=11111111-2222,v1=" + vectorHex,
		"short hex":      "t=1788150000,n=11111111-2222,v1=abcdef",
		"duplicated key": "t=1788150000,t=1788150001,n=11111111-2222,v1=" + vectorHex,
		"unknown key":    "t=1788150000,n=11111111-2222,v1=" + vectorHex + ",v0=x",
		"no value":       "t=1788150000,n=,v1=" + vectorHex,
	}

	for name, header := range cases {
		if _, err := Parse(header); !errors.Is(err, ErrMalformed) {
			t.Fatalf("%s: got %v, want ErrMalformed", name, err)
		}
	}
}
