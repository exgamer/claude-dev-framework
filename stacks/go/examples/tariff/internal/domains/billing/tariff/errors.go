package tariff

import "errors"

// ErrDefaultTariffTaken У парковки уже есть тариф по умолчанию: запись флага нарушила уникальный индекс (параллельная смена)
var ErrDefaultTariffTaken = errors.New("default tariff already set for parking")

// ErrTariffNotFound Запись изменила 0 строк: тариф удалён между чтением и записью
var ErrTariffNotFound = errors.New("tariff not found")
