package txfakes

import "context"

// Manager2 Фейк менеджера транзакции на два репозитория: вызывает fn с переданными фейками, без отката
type Manager2[R1, R2 any] struct {
	repository1 R1
	repository2 R2
}

func NewManager2[R1, R2 any](repository1 R1, repository2 R2) *Manager2[R1, R2] {
	return &Manager2[R1, R2]{
		repository1: repository1,
		repository2: repository2,
	}
}

func (m *Manager2[R1, R2]) Exec(ctx context.Context, fn func(ctx context.Context, repository1 R1, repository2 R2) error) error {
	return fn(ctx, m.repository1, m.repository2)
}
