package parking

import "example.com/parking-service/internal/common"

// Parking Парковка
type Parking struct {
	ID uint
	// IsActive Парковка принимает машины; на отключённую новые тарифы не заводятся
	IsActive bool
	// Currency Валюта расчётов парковки; тариф заводится только в ней
	Currency common.Currency
}
