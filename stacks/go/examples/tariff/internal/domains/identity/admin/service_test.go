package admin

import (
	"context"
	"errors"
	"testing"
	"time"
)

const testGuardName = "admin-api"

type fakeVerifier struct {
	claims *TokenClaims
	err    error
}

func (f fakeVerifier) Verify(string) (*TokenClaims, error) {
	return f.claims, f.err
}

type fakeRepository struct {
	admin       *Admin
	permissions []string
	parkingIDs  []uint
	err         error
	calls       int
}

func (f *fakeRepository) GetByID(context.Context, uint) (*Admin, error) {
	f.calls++

	return f.admin, f.err
}

func (f *fakeRepository) GetPermissionNames(context.Context, uint) ([]string, error) {
	return f.permissions, nil
}

func (f *fakeRepository) GetAllowedParkingIDs(context.Context, uint) ([]uint, error) {
	return f.parkingIDs, nil
}

type fakeCacheRepository struct {
	items map[uint]*Admin
}

func newFakeCacheRepository() *fakeCacheRepository {
	return &fakeCacheRepository{items: map[uint]*Admin{}}
}

func (c *fakeCacheRepository) Get(_ context.Context, id uint) (*Admin, error) {
	return c.items[id], nil
}

func (c *fakeCacheRepository) Set(_ context.Context, a *Admin, _ time.Duration) error {
	c.items[a.ID] = a

	return nil
}

func newService(verifier TokenVerifier, repository Repository) *Service {
	settings := AuthSettings{GuardName: testGuardName, CacheTTL: time.Minute}

	return NewService(verifier, repository, newFakeCacheRepository(), settings)
}

func validClaims(adminID uint) fakeVerifier {
	return fakeVerifier{claims: &TokenClaims{AdminID: adminID, GuardName: testGuardName}}
}

func TestAuthenticateLoadsPermissionsAndParkings(t *testing.T) {
	repository := &fakeRepository{
		admin:       &Admin{ID: 7},
		permissions: []string{"parking.admin.sessions.view"},
		parkingIDs:  []uint{3},
	}
	service := newService(validClaims(7), repository)

	admin, err := service.Authenticate(context.Background(), "token")
	if err != nil {
		t.Fatal(err)
	}

	if len(admin.Permissions) != 1 || len(admin.AllowedParkingIDs) != 1 || admin.AllowedParkingIDs[0] != 3 {
		t.Fatalf("permissions or parkings not loaded: %+v", admin)
	}

	if _, err := service.Authenticate(context.Background(), "token"); err != nil || repository.calls != 1 {
		t.Fatalf("second call must hit cache, repository calls = %d", repository.calls)
	}
}

func TestAuthenticateRejects(t *testing.T) {
	revokedAt := time.Now()
	storageErr := errors.New("db down")

	cases := map[string]struct {
		verifier   fakeVerifier
		repository *fakeRepository
		want       error
	}{
		"bad signature": {fakeVerifier{err: errors.New("boom")}, &fakeRepository{}, ErrInvalidToken},
		"other guard":   {fakeVerifier{claims: &TokenClaims{AdminID: 1, GuardName: "api"}}, &fakeRepository{admin: &Admin{ID: 1}}, ErrUnauthorized},
		"unknown admin": {validClaims(1), &fakeRepository{}, ErrUnauthorized},
		"revoked":       {validClaims(1), &fakeRepository{admin: &Admin{ID: 1, RevokedAt: &revokedAt}}, ErrUnauthorized},
		"storage error": {validClaims(1), &fakeRepository{err: storageErr}, storageErr},
	}

	for name, tc := range cases {
		service := newService(tc.verifier, tc.repository)

		_, err := service.Authenticate(context.Background(), "token")
		if !errors.Is(err, tc.want) {
			t.Fatalf("%s: got %v, want %v", name, err, tc.want)
		}
	}
}
