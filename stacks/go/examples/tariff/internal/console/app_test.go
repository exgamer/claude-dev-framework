package console

import (
	"context"
	"errors"
	"testing"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
	"github.com/stretchr/testify/assert"
)

// stopRecorder Ядро, которое запоминает порядок Stop
type stopRecorder struct {
	name    string
	stopped *[]string
	err     error
}

func (k *stopRecorder) Name() string { return k.name }

func (k *stopRecorder) Init(*app.App) error { return nil }

func (k *stopRecorder) Start(*app.App) error { return nil }

func (k *stopRecorder) Stop(context.Context) error {
	*k.stopped = append(*k.stopped, k.name)

	return k.err
}

func TestClose_SentryFlushesLast(t *testing.T) {
	var stopped []string

	consoleApp := &App{closers: closersOf(
		&stopRecorder{name: "sentry", stopped: &stopped},
		&stopRecorder{name: "postgres", stopped: &stopped, err: errors.New("close failed")},
		&stopRecorder{name: "rabbit", stopped: &stopped},
	)}

	consoleApp.Close()

	assert.Equal(t, []string{"rabbit", "postgres", "sentry"}, stopped, "ошибка закрытия не прерывает Close, Sentry — последним")
}
