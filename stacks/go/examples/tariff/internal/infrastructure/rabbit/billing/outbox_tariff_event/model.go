package outbox_tariff_event

// eventMessage Тело сообщения о тарифе
type eventMessage struct {
	EventID      uint   `json:"event_id"`
	TariffID     uint   `json:"tariff_id"`
	ParkingID    uint   `json:"parking_id"`
	Name         string `json:"name"`
	Currency     string `json:"currency"`
	GraceMinutes uint   `json:"grace_minutes"`
	Price        uint   `json:"price"`
}
