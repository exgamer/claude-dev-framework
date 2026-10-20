package outbox_tariff_event

import "time"

// outboxTariffEvent Событие о тарифе для отправки в RabbitMQ (outbox)
type outboxTariffEvent struct {
	ID           uint   `gorm:"column:id;primaryKey;autoIncrement"`
	TariffID     uint   `gorm:"column:tariff_id"`
	ParkingID    uint   `gorm:"column:parking_id"`
	Name         string `gorm:"column:name"`
	Currency     string `gorm:"column:currency"`
	GraceMinutes uint   `gorm:"column:grace_minutes"`
	Price        uint   `gorm:"column:price"`
	RoutingKey   string `gorm:"column:routing_key"`
	// Status 1 — pending, 2 — published, 3 — failed, 4 — awaiting_retry
	Status      uint       `gorm:"column:status"`
	Attempts    uint       `gorm:"column:attempts"`
	PublishedAt *time.Time `gorm:"column:published_at"`
	CreatedAt   time.Time  `gorm:"column:created_at"`
	// UpdatedAt Меняется при каждой попытке; по нему повтор находит зависшие события
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (outboxTariffEvent) TableName() string {
	return "parking.outbox_tariff_events"
}
