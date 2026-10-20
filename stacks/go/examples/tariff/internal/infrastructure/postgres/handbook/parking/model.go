package parking

import "time"

// parking Парковка
type parking struct {
	ID       uint `gorm:"column:id;primaryKey"`
	IsActive bool `gorm:"column:is_active"`
	// Currency Валюта расчётов (ISO 4217)
	Currency string `gorm:"column:currency"`
	// DeletedAt Мягкое удаление; удалённая парковка не читается
	DeletedAt *time.Time `gorm:"column:deleted_at"`
}

func (parking) TableName() string {
	return "parking.parkings"
}
