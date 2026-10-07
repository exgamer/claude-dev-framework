package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/structures"
)

// tariffData Тариф в ответе
type tariffData struct {
	ID           uint   `json:"id"`
	ParkingID    uint   `json:"parking_id"`
	Name         string `json:"name"`
	Currency     string `json:"currency"`
	IsDefault    bool   `json:"is_default"`
	GraceMinutes uint   `json:"grace_minutes"`
}

type tariffResponse struct {
	structures.Response[tariffData]
}

type tariffListResponse struct {
	structures.Response[pagination.Paginated[tariffData]]
}
