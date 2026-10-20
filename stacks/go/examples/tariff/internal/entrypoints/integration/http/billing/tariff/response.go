package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/structures"
)

// tariffData Тариф для партнёра: то, что нужно для расчёта стоимости
type tariffData struct {
	ID           uint   `json:"id"`
	ParkingID    uint   `json:"parking_id"`
	Currency     string `json:"currency"`
	IsActive     bool   `json:"is_active"`
	GraceMinutes uint   `json:"grace_minutes"`
	Price        uint   `json:"price"`
}

type tariffResponse struct {
	structures.Response[tariffData]
}
