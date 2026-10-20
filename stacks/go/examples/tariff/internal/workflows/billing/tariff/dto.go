package tariff

import "example.com/parking-service/internal/common"

// CreateTariffParams Данные для создания тарифа
type CreateTariffParams struct {
	ParkingID    uint
	Name         string
	Currency     common.Currency
	GraceMinutes uint
	Price        uint
}
