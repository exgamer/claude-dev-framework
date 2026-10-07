package handbookfakes

import (
	"context"

	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
)

// ParkingRepository In-memory parkingdomain.Repository для unit-тестов
type ParkingRepository struct {
	Items map[uint]*parkingdomain.Parking
}

func NewParkingRepository(items ...*parkingdomain.Parking) *ParkingRepository {
	r := &ParkingRepository{Items: map[uint]*parkingdomain.Parking{}}

	for _, item := range items {
		r.Items[item.ID] = item
	}

	return r
}

func (r *ParkingRepository) GetByID(_ context.Context, id uint) (*parkingdomain.Parking, error) {
	return r.Items[id], nil
}
