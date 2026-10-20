package parking

import "time"

// parking Парковка
type parking struct {
	ID uint `gorm:"column:id;primaryKey"`
	// LPMHost Хост ЛПМ для доставки изменений; NULL — у парковки нет ЛПМ
	LPMHost  *string `gorm:"column:lpm_host"`
	IsActive bool    `gorm:"column:is_active"`
	// DeletedAt Мягкое удаление; удалённая парковка не читается
	DeletedAt *time.Time `gorm:"column:deleted_at"`
}

func (parking) TableName() string {
	return "parking.parkings"
}
