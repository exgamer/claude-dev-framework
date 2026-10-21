package tariff

import "example.com/parking-service/internal/common"

// tariff Тариф в кеше: состояние сущности неэкспортируемое и в JSON не попало бы — кладётся модель.
// Имена полей без тегов совпадают с прежним форматом (сущность целиком), записи до выкладки читаются
type tariff struct {
	ID           uint
	ParkingID    uint
	Name         string
	Currency     common.Currency
	IsDefault    bool
	IsActive     bool
	GraceMinutes uint
	Price        uint
}
