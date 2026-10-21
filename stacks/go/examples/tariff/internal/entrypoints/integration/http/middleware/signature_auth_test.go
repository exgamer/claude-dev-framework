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

	"example.com/parking-service/pkg/requestbody"
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
		body, readErr := io.ReadAll(c.Request.Body)
		if readErr != nil {
			c.Status(http.StatusInternalServerError)

			return
		}

		*received = string(body)
		c.Status(http.StatusOK)
	})

	return r, received
}

// signing Подпись POST-запроса на signedPath
type signing struct {
	secret    string
	timestamp int64
	nonce     string
	body      string
}

func (s signing) header() string {
	request := signature.Request{
		Timestamp: s.timestamp,
		Nonce:     s.nonce,
		Message:   signature.Message{Method: http.MethodPost, Path: signedPath, Body: []byte(s.body)},
	}

	return signature.HeaderValue(s.secret, request)
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

func TestSignatureAuthMiddleware_GetWithoutBodyKeepsNoBody(t *testing.T) {
	gin.SetMode(gin.TestMode)

	keptNoBody := false
	r := gin.New()
	r.Use(gosdkmiddleware.FormattedResponseMiddleware())
	r.GET("/tariffs/:id", SignatureAuthMiddleware(newPartnerService(false), testSignatureSettings), func(c *gin.Context) {
		keptNoBody = c.Request.Body == http.NoBody
		c.Status(http.StatusOK)
	})

	header := signature.HeaderValue(testSecret, signature.Request{
		Timestamp: time.Now().Unix(),
		Nonce:     "nonce-get-0001",
		Message:   signature.Message{Method: http.MethodGet, Path: signedPath},
	})
	req := httptest.NewRequest(http.MethodGet, signedPath, nil)
	req.Header.Set(signature.HeaderName, header)

	require.Equal(t, http.StatusOK, serve(r, req))
	assert.True(t, keptNoBody, "GET без тела: хендлер получает http.NoBody, а не подменённое пустое тело")
}

func TestSignatureAuthMiddleware_ValidSignatureKeepsBody(t *testing.T) {
	r, received := signedRouter(false)
	body := `{"event":"ping"}`
	header := signing{testSecret, time.Now().Unix(), "nonce-0001", body}.header()

	request := signedRequest(header, signedPath, body)

	require.Equal(t, http.StatusOK, serve(r, request))
	assert.Equal(t, body, *received, "хендлер получает тело целиком")
}

func TestSignatureAuthMiddleware_RejectsReplay(t *testing.T) {
	r, _ := signedRouter(false)
	header := signing{secret: testSecret, timestamp: time.Now().Unix(), nonce: "nonce-0002"}.header()

	first := signedRequest(header, signedPath, "")
	replay := signedRequest(header, signedPath, "")

	require.Equal(t, http.StatusOK, serve(r, first))
	assert.Equal(t, http.StatusUnauthorized, serve(r, replay))
}

func TestSignatureAuthMiddleware_RejectedSignatureKeepsNonceFree(t *testing.T) {
	r, _ := signedRouter(false)
	now := time.Now().Unix()
	forged := signing{secret: "other", timestamp: now, nonce: "nonce-0011"}.header()
	valid := signing{secret: testSecret, timestamp: now, nonce: "nonce-0011"}.header()

	forgedRequest := signedRequest(forged, signedPath, "")
	validRequest := signedRequest(valid, signedPath, "")

	require.Equal(t, http.StatusUnauthorized, serve(r, forgedRequest))
	assert.Equal(t, http.StatusOK, serve(r, validRequest), "неподписанный запрос не занимает чужой nonce")
}

func TestSignatureAuthMiddleware_Rejects(t *testing.T) {
	now := time.Now().Unix()
	body := `{"event":"ping"}`

	cases := []struct {
		name string
		// sign Пустой secret — заголовок берётся из header как есть
		sign               signing
		header, path, body string
		storageFails       bool
		want               int
	}{
		{name: "no header", path: signedPath, body: body, want: http.StatusUnauthorized},
		{name: "other secret", sign: signing{"other", now, "nonce-0003", body}, path: signedPath, body: body, want: http.StatusUnauthorized},
		{name: "body changed", sign: signing{testSecret, now, "nonce-0004", body}, path: signedPath, body: `{"event":"pong"}`, want: http.StatusUnauthorized},
		{name: "query changed", sign: signing{testSecret, now, "nonce-0005", body}, path: "/tariffs/1?expand=all", body: body, want: http.StatusUnauthorized},
		{name: "expired", sign: signing{testSecret, now - 600, "nonce-0006", body}, path: signedPath, body: body, want: http.StatusUnauthorized},
		{name: "from the future", sign: signing{testSecret, now + 120, "nonce-0009", body}, path: signedPath, body: body, want: http.StatusUnauthorized},
		{name: "malformed header", header: "t=abc,n=nonce-0010,v1=00", path: signedPath, body: body, want: http.StatusUnauthorized},
		{name: "body too large", sign: signing{secret: testSecret, timestamp: now, nonce: "nonce-0007"}, path: signedPath, body: strings.Repeat("x", requestbody.DefaultLimit+1), want: http.StatusRequestEntityTooLarge},
		{name: "nonce storage error", sign: signing{testSecret, now, "nonce-0008", body}, path: signedPath, body: body, storageFails: true, want: http.StatusInternalServerError},
	}

	for _, tc := range cases {
		r, _ := signedRouter(tc.storageFails)
		header := tc.header

		if tc.sign.secret != "" {
			header = tc.sign.header()
		}

		request := signedRequest(header, tc.path, tc.body)

		if got := serve(r, request); got != tc.want {
			t.Fatalf("%s: got %d, want %d", tc.name, got, tc.want)
		}
	}
}
