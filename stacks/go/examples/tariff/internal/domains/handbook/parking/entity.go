package parking

// Parking Парковка
type Parking struct {
	// ID
	ID uint
	// LPMHost Хост ЛПМ для доставки изменений; nil — у парковки нет ЛПМ
	LPMHost *string
	// IsActive Парковка принимает машины; на отключённую новые тарифы не заводятся
	IsActive bool
}
