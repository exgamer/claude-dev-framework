package txfakes

import "context"

// Manager3 Фейк менеджера транзакции на три репозитория: вызывает fn с переданными фейками, без отката; Calls — сколько раз открывалась транзакция
type Manager3[R1, R2, R3 any] struct {
	repository1 R1
	repository2 R2
	repository3 R3
	Calls       int
}

func NewManager3[R1, R2, R3 any](repository1 R1, repository2 R2, repository3 R3) *Manager3[R1, R2, R3] {
	return &Manager3[R1, R2, R3]{
		repository1: repository1,
		repository2: repository2,
		repository3: repository3,
	}
}

func (m *Manager3[R1, R2, R3]) Exec(ctx context.Context, fn func(ctx context.Context, repository1 R1, repository2 R2, repository3 R3) error) error {
	m.Calls++

	return fn(ctx, m.repository1, m.repository2, m.repository3)
}

// Manager Фейк менеджера транзакции на один репозиторий; Calls — сколько раз открывалась транзакция
type Manager[R any] struct {
	repository R
	Calls      int
}

func NewManager[R any](repository R) *Manager[R] {
	return &Manager[R]{repository: repository}
}

func (m *Manager[R]) Exec(ctx context.Context, fn func(ctx context.Context, repository R) error) error {
	m.Calls++

	return fn(ctx, m.repository)
}
