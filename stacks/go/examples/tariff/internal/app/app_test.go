package app

import (
	"testing"

	"github.com/redis/go-redis/v9"
)

func TestRequireRedisContextTimeout(t *testing.T) {
	cases := map[string]struct {
		enabled bool
		wantErr bool
	}{
		"enabled":  {true, false},
		"disabled": {false, true},
	}

	for name, tc := range cases {
		client := redis.NewClient(&redis.Options{Addr: "localhost:0", ContextTimeoutEnabled: tc.enabled})

		err := requireRedisContextTimeout(client)
		if closeErr := client.Close(); closeErr != nil {
			t.Fatalf("%s: close: %v", name, closeErr)
		}

		if (err != nil) != tc.wantErr {
			t.Fatalf("%s: got %v, wantErr %v", name, err, tc.wantErr)
		}
	}
}
