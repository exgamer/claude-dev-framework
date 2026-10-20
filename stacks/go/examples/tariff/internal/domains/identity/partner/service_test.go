package partner

import (
	"context"
	"errors"
	"testing"
	"time"
)

const testNonce = "nonce-0001"

type fakeNonceRepository struct {
	seen map[string]bool
	ttl  time.Duration
	err  error
}

func (f *fakeNonceRepository) Remember(_ context.Context, nonce string, ttl time.Duration) (bool, error) {
	f.ttl = ttl

	if f.err != nil {
		return false, f.err
	}

	if f.seen[nonce] {
		return false, nil
	}

	f.seen[nonce] = true

	return true, nil
}

func newService(repository *fakeNonceRepository) *Service {
	return NewService(repository, AuthSettings{
		APIKey:   "partner-key",
		NonceTTL: 2 * time.Minute,
	})
}

func TestConsumeNonceRejectsReplay(t *testing.T) {
	repository := &fakeNonceRepository{seen: map[string]bool{}}
	service := newService(repository)

	if err := service.ConsumeNonce(context.Background(), testNonce); err != nil {
		t.Fatal(err)
	}

	if repository.ttl != 2*time.Minute {
		t.Fatalf("nonce ttl: got %v, want NonceTTL", repository.ttl)
	}

	if err := service.ConsumeNonce(context.Background(), testNonce); !errors.Is(err, ErrNonceReplayed) {
		t.Fatalf("replay: got %v, want ErrNonceReplayed", err)
	}
}

func TestConsumeNonceStorageError(t *testing.T) {
	storageErr := errors.New("redis down")
	service := newService(&fakeNonceRepository{err: storageErr})

	err := service.ConsumeNonce(context.Background(), testNonce)
	if !errors.Is(err, storageErr) || errors.Is(err, ErrNonceReplayed) {
		t.Fatalf("got %v, want storage error, not replay", err)
	}
}

func TestAuthenticateAPIKey(t *testing.T) {
	service := newService(&fakeNonceRepository{})

	if err := service.AuthenticateAPIKey("partner-key"); err != nil {
		t.Fatal(err)
	}

	for _, key := range []string{"", "partner-ke", "partner-key2"} {
		if err := service.AuthenticateAPIKey(key); !errors.Is(err, ErrInvalidAPIKey) {
			t.Fatalf("key %q: got %v, want ErrInvalidAPIKey", key, err)
		}
	}
}
