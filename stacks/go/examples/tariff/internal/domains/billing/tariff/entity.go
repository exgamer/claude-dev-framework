package tariff

// Tariff Тариф парковки
type Tariff struct {
	// ID
	ID uint
	// ParkingID
	ParkingID uint
	// Name
	Name string
	// Currency Валюта (ISO 4217)
	Currency string
	// IsDefault Тариф по умолчанию для парковки, у парковки он один
	IsDefault bool
	// IsActive Тариф применяется; снимается при отключении парковки в справочнике
	IsActive bool
	// GraceMinutes Доп. время на выезд после оплаты (минуты). 0 = отключено
	GraceMinutes uint
	// Price Цена часа стоянки в минимальных единицах валюты (тиын для KZT)
	Price uint
}

// PriceLimit Лимит цены парковки из внешнего реестра парковок
type PriceLimit struct {
	// ParkingID
	ParkingID uint
	// MaxPrice Верхняя граница цены часа в минимальных единицах валюты тарифа
	MaxPrice uint
}
