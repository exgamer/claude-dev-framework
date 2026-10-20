package requestbody

import (
	"errors"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestReadLimitedWithoutBodyKeepsNoBody(t *testing.T) {
	// httptest.NewRequest без тела ставит http.NoBody — как сервер на GET
	req := httptest.NewRequest(http.MethodGet, "/tariffs", nil)

	body, err := ReadLimited(httptest.NewRecorder(), req, 16)
	if err != nil {
		t.Fatal(err)
	}

	if body != nil {
		t.Fatalf("expected nil body, got %q", body)
	}

	if req.Body != http.NoBody {
		t.Fatalf("expected http.NoBody, got %T", req.Body)
	}
}

func TestReadLimitedEmptyBodyBecomesNoBody(t *testing.T) {
	req := httptest.NewRequest(http.MethodPost, "/tariffs", nil)
	// Тело, подменённое предыдущим обработчиком: не NoBody, но пустое
	req.Body = io.NopCloser(strings.NewReader(""))

	body, err := ReadLimited(httptest.NewRecorder(), req, 16)
	if err != nil {
		t.Fatal(err)
	}

	if body != nil || req.Body != http.NoBody {
		t.Fatalf("expected nil body and http.NoBody, got %q and %T", body, req.Body)
	}
}

func TestReadLimitedReturnsBodyToRequest(t *testing.T) {
	req := httptest.NewRequest(http.MethodPost, "/tariffs", strings.NewReader(`{"a":1}`))

	body, err := ReadLimited(httptest.NewRecorder(), req, 16)
	if err != nil {
		t.Fatal(err)
	}

	again, err := io.ReadAll(req.Body)
	if err != nil {
		t.Fatal(err)
	}

	if string(body) != `{"a":1}` || string(again) != `{"a":1}` {
		t.Fatalf("got %q, request body %q", body, again)
	}
}

func TestReadLimitedRejectsOversizedBody(t *testing.T) {
	cases := map[string]struct {
		body    string
		wantErr error
	}{
		"exactly limit": {strings.Repeat("x", 16), nil},
		"limit + 1":     {strings.Repeat("x", 17), ErrTooLarge},
	}

	for name, tc := range cases {
		req := httptest.NewRequest(http.MethodPost, "/tariffs", strings.NewReader(tc.body))

		_, err := ReadLimited(httptest.NewRecorder(), req, 16)
		if !errors.Is(err, tc.wantErr) {
			t.Fatalf("%s: got %v, want %v", name, err, tc.wantErr)
		}
	}
}
