package tariff

import "errors"

// ErrDefaultTariffTaken У парковки уже есть тариф по умолчанию: запись флага нарушила уникальный индекс (параллельная смена)
var ErrDefaultTariffTaken = errors.New("default tariff already set for parking")
