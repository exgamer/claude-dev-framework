package tariff

// Search Фильтр списка тарифов
type Search struct {
	ParkingID uint
	Page      uint
	PerPage   uint
}

// Patch Частичное обновление тарифа: nil — поле не меняется
type Patch struct {
	Name         *string
	GraceMinutes *uint
}
