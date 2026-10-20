package txfakes

import "context"

// Manager2 Фейк менеджера транзакции на два репозитория: вызывает fn с переданными фейками, без отката; Calls — сколько раз открывалась транзакция
type Manager2[R1, R2 any] struct {
	repository1 R1
	repository2 R2
	Calls       int
}

func NewManager2[R1, R2 any](repository1 R1, repository2 R2) *Manager2[R1, R2] {
	return &Manager2[R1, R2]{
		repository1: repository1,
		repository2: repository2,
	}
}

func (m *Manager2[R1, R2]) Exec(ctx context.Context, fn func(ctx context.Context, repository1 R1, repository2 R2) error) error {
	m.Calls++

	return fn(ctx, m.repository1, m.repository2)
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
