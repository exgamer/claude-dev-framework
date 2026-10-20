package tariff

import "slices"

// ParkingScope Скоуп парковок из точки входа: нулевое значение — ни одной, «без ограничения» — только All: true
type ParkingScope struct {
	All bool
	IDs []uint
}

// Allows Парковка в скоупе: при All — любая, иначе — из IDs; пустой IDs — ни одной
func (s ParkingScope) Allows(parkingID uint) bool {
	return s.All || slices.Contains(s.IDs, parkingID)
}

// Search Фильтр списка тарифов
type Search struct {
	ParkingID uint
	// Scope Скоуп из точки входа: нулевое значение — ни одной парковки, все — только All: true
	Scope   ParkingScope
	Page    uint
	PerPage uint
}

// Patch Частичное обновление тарифа: nil — поле не меняется
type Patch struct {
	Name         *string
	GraceMinutes *uint
}
