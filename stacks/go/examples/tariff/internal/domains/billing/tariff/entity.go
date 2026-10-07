package tariff

// Tariff Тариф парковки
type Tariff struct {
	// ID ID тарифа
	ID uint
	// ParkingID ID парковки
	ParkingID uint
	// Name Название тарифа
	Name string
	// Currency Валюта (ISO 4217)
	Currency string
	// IsDefault Тариф по умолчанию для парковки, у парковки он один
	IsDefault bool
	// GraceMinutes Доп. время на выезд после оплаты (минуты). 0 = отключено
	GraceMinutes uint
}
