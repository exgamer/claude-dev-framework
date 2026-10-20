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
	vectorPath      = "/api/integration/v1/billing/tariffs/42"
	vectorBody      = `{"a":1}`
	vectorHex       = "dd308087b520b5821cf5f1269857c8f9746f5d18463bb2c856d8244ac3bbc512"
)

func vectorRequest(method string) Request {
	return Request{
		Timestamp: vectorTimestamp,
		Nonce:     vectorNonce,
		Message:   Message{Method: method, Path: vectorPath, Body: []byte(vectorBody)},
	}
}

func TestSignMatchesVector(t *testing.T) {
	got := Sign(vectorSecret, vectorRequest("post"))
	if got != vectorHex {
		t.Fatalf("got %s, want %s", got, vectorHex)
	}
}

func TestParseAndVerifyRoundTrip(t *testing.T) {
	header := HeaderValue(vectorSecret, vectorRequest("POST"))

	parsed, err := Parse(header)
	if err != nil {
		t.Fatal(err)
	}

	err = Verify(VerifyParams{
		Secret:    vectorSecret,
		Parsed:    parsed,
		Message:   vectorRequest("POST").Message,
		Tolerance: time.Minute,
		Now:       time.Unix(vectorTimestamp+10, 0),
	})
	if err != nil {
		t.Fatal(err)
	}
}

func TestVerifyRejects(t *testing.T) {
	header := HeaderValue(vectorSecret, vectorRequest("POST"))

	parsed, err := Parse(header)
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
		"other path":   {vectorSecret, "POST", "/api/integration/v1/billing/tariffs/43", vectorBody, inWindow, ErrMismatch},
		"other method": {vectorSecret, "GET", vectorPath, vectorBody, inWindow, ErrMismatch},
		"other body":   {vectorSecret, "POST", vectorPath, `{"a":2}`, inWindow, ErrMismatch},
		"too old":      {vectorSecret, "POST", vectorPath, vectorBody, inWindow.Add(2 * time.Minute), ErrExpired},
		"from future":  {vectorSecret, "POST", vectorPath, vectorBody, inWindow.Add(-2 * time.Minute), ErrExpired},
	}

	for name, tc := range cases {
		err := Verify(VerifyParams{
			Secret:    tc.secret,
			Parsed:    parsed,
			Message:   Message{Method: tc.method, Path: tc.path, Body: []byte(tc.body)},
			Tolerance: time.Minute,
			Now:       tc.now,
		})
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
