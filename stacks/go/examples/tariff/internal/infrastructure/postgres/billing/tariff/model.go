package tariff

import "time"

// tariff Тариф парковки
type tariff struct {
	ID           uint       `gorm:"column:id;primaryKey;autoIncrement"`
	ParkingID    uint       `gorm:"column:parking_id"`
	Name         string     `gorm:"column:name"`
	Currency     string     `gorm:"column:currency"`
	IsDefault    bool       `gorm:"column:is_default"`
	GraceMinutes uint       `gorm:"column:grace_minutes"`
	CreatedAt    time.Time  `gorm:"column:created_at"`
	UpdatedAt    time.Time  `gorm:"column:updated_at"`
	DeletedAt    *time.Time `gorm:"column:deleted_at"`
}

func (tariff) TableName() string {
	return "parking.tariffs"
}
