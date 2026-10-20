package outbox_tariff_event

import (
	"time"

	"example.com/parking-service/internal/common"
)

// OutboxTariffEvent Событие о тарифе для отправки в RabbitMQ (outbox)
type OutboxTariffEvent struct {
	// ID ID записи; уходит в сообщение как event_id — по нему получатель отсекает повторную доставку
	ID        uint
	TariffID  uint
	ParkingID uint
	// Name Название тарифа на момент события
	Name string
	// Currency Валюта (ISO 4217)
	Currency common.Currency
	// GraceMinutes Доп. время на выезд после оплаты (минуты)
	GraceMinutes uint
	// Price Цена часа в минимальных единицах валюты
	Price uint
	// RoutingKey Routing key публикации, он же тип события
	RoutingKey string
	Status     Status
	// Attempts Неудачных попыток отправки
	Attempts uint
	// PublishedAt Когда брокер подтвердил публикацию
	PublishedAt *time.Time
}

// IsDeliverable Событие ещё ждёт отправки; опубликованное и исчерпавшее попытки повторно не отправляются
func (e *OutboxTariffEvent) IsDeliverable() bool {
	return e.Status == StatusPending || e.Status == StatusAwaitingRetry
}

// NewTariffCreatedEvent Событие tariff.created, ждёт отправки
func NewTariffCreatedEvent(tariff CreatedTariff) *OutboxTariffEvent {
	return &OutboxTariffEvent{
		TariffID:     tariff.TariffID,
		ParkingID:    tariff.ParkingID,
		Name:         tariff.Name,
		Currency:     tariff.Currency,
		GraceMinutes: tariff.GraceMinutes,
		Price:        tariff.Price,
		RoutingKey:   RoutingKeyTariffCreated,
		Status:       StatusPending,
	}
}
