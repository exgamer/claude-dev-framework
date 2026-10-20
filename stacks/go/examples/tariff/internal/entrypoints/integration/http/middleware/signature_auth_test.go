package middleware

import (
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"

	gosdkmiddleware "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware"
	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"example.com/parking-service/pkg/signature"
)

const signedPath = "/tariffs/1?expand=price"

// signedRouter Хендлер запоминает тело, которое до него дошло
func signedRouter(storageFails bool) (*gin.Engine, *string) {
	gin.SetMode(gin.TestMode)

	received := new(string)
	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.POST("/tariffs/:id", SignatureAuthMiddleware(newPartnerService(storageFails), testSignatureSettings), func(c *gin.Context) {
		body, _ := io.ReadAll(c.Request.Body)
		*received = string(body)
		c.Status(http.StatusOK)
	})

	return r, received
}

func signedRequest(header, path, body string) *http.Request {
	req := httptest.NewRequest(http.MethodPost, path, strings.NewReader(body))
	if header != "" {
		req.Header.Set(signature.HeaderName, header)
	}

	return req
}

func serve(r *gin.Engine, req *http.Request) int {
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	return w.Code
}

func TestSignatureAuthMiddleware_ValidSignatureKeepsBody(t *testing.T) {
	r, received := signedRouter(false)
	body := `{"event":"ping"}`
	header := signature.HeaderValue(testSecret, time.Now().Unix(), "nonce-0001", http.MethodPost, signedPath, []byte(body))

	require.Equal(t, http.StatusOK, serve(r, signedRequest(header, signedPath, body)))
	assert.Equal(t, body, *received, "хендлер получает тело целиком")
}

func TestSignatureAuthMiddleware_RejectsReplay(t *testing.T) {
	r, _ := signedRouter(false)
	header := signature.HeaderValue(testSecret, time.Now().Unix(), "nonce-0002", http.MethodPost, signedPath, nil)

	require.Equal(t, http.StatusOK, serve(r, signedRequest(header, signedPath, "")))
	assert.Equal(t, http.StatusUnauthorized, serve(r, signedRequest(header, signedPath, "")))
}

func TestSignatureAuthMiddleware_RejectedSignatureKeepsNonceFree(t *testing.T) {
	r, _ := signedRouter(false)
	now := time.Now().Unix()
	forged := signature.HeaderValue("other", now, "nonce-0011", http.MethodPost, signedPath, nil)
	valid := signature.HeaderValue(testSecret, now, "nonce-0011", http.MethodPost, signedPath, nil)

	require.Equal(t, http.StatusUnauthorized, serve(r, signedRequest(forged, signedPath, "")))
	assert.Equal(t, http.StatusOK, serve(r, signedRequest(valid, signedPath, "")), "неподписанный запрос не занимает чужой nonce")
}

func TestSignatureAuthMiddleware_Rejects(t *testing.T) {
	now := time.Now().Unix()
	body := `{"event":"ping"}`

	cases := []struct {
		name, header, path, body string
		storageFails             bool
		want                     int
	}{
		{"no header", "", signedPath, body, false, http.StatusUnauthorized},
		{"other secret", signature.HeaderValue("other", now, "nonce-0003", http.MethodPost, signedPath, []byte(body)), signedPath, body, false, http.StatusUnauthorized},
		{"body changed", signature.HeaderValue(testSecret, now, "nonce-0004", http.MethodPost, signedPath, []byte(body)), signedPath, `{"event":"pong"}`, false, http.StatusUnauthorized},
		{"query changed", signature.HeaderValue(testSecret, now, "nonce-0005", http.MethodPost, signedPath, []byte(body)), "/tariffs/1?expand=all", body, false, http.StatusUnauthorized},
		{"expired", signature.HeaderValue(testSecret, now-600, "nonce-0006", http.MethodPost, signedPath, []byte(body)), signedPath, body, false, http.StatusUnauthorized},
		{"from the future", signature.HeaderValue(testSecret, now+120, "nonce-0009", http.MethodPost, signedPath, []byte(body)), signedPath, body, false, http.StatusUnauthorized},
		{"malformed header", "t=abc,n=nonce-0010,v1=00", signedPath, body, false, http.StatusUnauthorized},
		{"body too large", signature.HeaderValue(testSecret, now, "nonce-0007", http.MethodPost, signedPath, nil), signedPath, strings.Repeat("x", maxSignedBodyBytes+1), false, http.StatusRequestEntityTooLarge},
		{"nonce storage error", signature.HeaderValue(testSecret, now, "nonce-0008", http.MethodPost, signedPath, []byte(body)), signedPath, body, true, http.StatusInternalServerError},
	}

	for _, tc := range cases {
		r, _ := signedRouter(tc.storageFails)

		if got := serve(r, signedRequest(tc.header, tc.path, tc.body)); got != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, got, tc.want)
		}
	}
}
