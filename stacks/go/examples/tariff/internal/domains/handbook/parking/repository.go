package parking

import "context"

type Repository interface {
	GetByID(ctx context.Context, id uint) (*Parking, error)
}
