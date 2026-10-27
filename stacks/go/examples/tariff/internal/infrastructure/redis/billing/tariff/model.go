package tariff

import "example.com/parking-service/internal/common"

// tariff Тариф в кеше; имена полей без тегов совпадают с прежним форматом (сущность целиком), записи до выкладки читаются
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
