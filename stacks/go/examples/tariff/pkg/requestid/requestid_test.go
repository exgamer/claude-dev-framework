package requestid

import (
	"context"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/config"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/constants"
)

func TestHeader(t *testing.T) {
	cases := map[string]struct {
		ctx  context.Context
		want string
	}{
		"http request":     {context.WithValue(context.Background(), constants.HttpInfoKey, &config.HttpInfo{RequestId: "req-1"}), "req-1"},
		"outside http":     {context.Background(), ""},
		"empty request id": {context.WithValue(context.Background(), constants.HttpInfoKey, &config.HttpInfo{}), ""},
	}

	for name, tc := range cases {
		headers := Header(tc.ctx)

		if headers[constants.RequestIdHeaderName] != tc.want {
			t.Fatalf("%s: got %q, want %q", name, headers[constants.RequestIdHeaderName], tc.want)
		}

		if tc.want == "" && len(headers) != 0 {
			t.Fatalf("%s: expected no headers, got %v", name, headers)
		}
	}
}
