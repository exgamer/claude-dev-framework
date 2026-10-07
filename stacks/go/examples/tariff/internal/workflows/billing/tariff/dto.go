package tariff

// CreateTariffParams Данные для создания тарифа
type CreateTariffParams struct {
	ParkingID    uint
	Name         string
	Currency     string
	GraceMinutes uint
}
