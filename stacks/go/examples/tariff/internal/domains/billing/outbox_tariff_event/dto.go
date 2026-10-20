package outbox_tariff_event

import "time"

// CreatedTariff Данные созданного тарифа для события tariff.created
type CreatedTariff struct {
	TariffID     uint
	ParkingID    uint
	Name         string
	Currency     string
	GraceMinutes uint
	Price        uint
}

// RetrySettings Настройки повтора из конфига
type RetrySettings struct {
	// BatchSize Событий за одну выборку
	BatchSize uint
	// StaleAfter Сколько событие не менялось, чтобы считаться зависшим; на столько же взятое событие скрыто от параллельного прогона
	StaleAfter time.Duration
}

// RetryResult Итог прогона повтора
type RetryResult struct {
	Published uint
	Failed    uint
}
