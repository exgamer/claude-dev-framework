package outbox_tariff_event

// Status Статус доставки события
type Status uint

const (
	// StatusPending Ожидает первой отправки
	StatusPending Status = iota + 1
	// StatusPublished Брокер подтвердил публикацию
	StatusPublished
	// StatusFailed Попытки исчерпаны — ручной разбор (чем выражается разбор — README)
	StatusFailed
	// StatusAwaitingRetry Отправка не удалась; повторит консольная команда по расписанию
	StatusAwaitingRetry
)
