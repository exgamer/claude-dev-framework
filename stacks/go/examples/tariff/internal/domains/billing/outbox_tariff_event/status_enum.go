package outbox_tariff_event

// Status Статус доставки события
type Status uint

const (
	StatusPending Status = iota + 1
	StatusPublished
	StatusFailed
	StatusAwaitingRetry
)

func (s Status) Label() string {
	switch s {
	case StatusPending:
		return "Ожидает отправки"
	case StatusPublished:
		return "Опубликовано в RabbitMQ"
	case StatusFailed:
		return "Попытки исчерпаны — ручной разбор"
	case StatusAwaitingRetry:
		return "Отправка не удалась; повторит консольная команда по расписанию"
	default:
		return "Неизвестный статус"
	}
}
