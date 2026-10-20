package middleware

import (
	"context"
	"errors"
	"time"

	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
)

const (
	testAPIKey = "partner-key"
	testSecret = "partner-secret"
)

// memoryNonceRepository nonce в памяти; err — сбой хранилища
type memoryNonceRepository struct {
	seen map[string]bool
	err  error
}

func (m *memoryNonceRepository) Remember(_ context.Context, nonce string, _ time.Duration) (bool, error) {
	if m.err != nil {
		return false, m.err
	}

	if m.seen[nonce] {
		return false, nil
	}

	m.seen[nonce] = true

	return true, nil
}

// testSignatureSettings Окно минута: подпись старше — 401
var testSignatureSettings = SignatureSettings{Secret: testSecret, Tolerance: time.Minute}

func newPartnerService(storageFails bool) *partnerdomain.Service {
	repository := &memoryNonceRepository{seen: map[string]bool{}}
	if storageFails {
		repository.err = errors.New("redis down")
	}

	return partnerdomain.NewService(repository, partnerdomain.AuthSettings{
		APIKey:   testAPIKey,
		NonceTTL: 2 * time.Minute,
	})
}
