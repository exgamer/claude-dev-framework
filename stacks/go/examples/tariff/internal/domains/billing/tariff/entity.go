package tariff

import "example.com/parking-service/internal/common"

// Tariff Тариф парковки
type Tariff struct {
	ID        uint
	ParkingID uint
	Name      string
	// Currency Валюта (ISO 4217)
	Currency common.Currency
	// GraceMinutes Доп. время на выезд после оплаты (минуты). 0 = отключено
	GraceMinutes uint
	// Price Цена часа стоянки в минимальных единицах валюты (тиын для KZT)
	Price uint
	// isDefault Тариф по умолчанию для парковки, у парковки он один
	isDefault bool
	// isActive Тариф применяется; снимается при отключении парковки в справочнике
	isActive bool
}

// NewTariff Новый тариф из полей без состояния: активный, не по умолчанию — флаг ставится отдельным переходом
func NewTariff(fields Tariff) *Tariff {
	fields.isDefault = false
	fields.isActive = true

	return &fields
}

// RestoreTariff Сборка тарифа из хранилища с сохранённым состоянием; только для мапперов инфраструктуры и тестов
func RestoreTariff(fields Tariff, isDefault, isActive bool) *Tariff {
	fields.isDefault = isDefault
	fields.isActive = isActive

	return &fields
}

func (t *Tariff) IsDefault() bool {
	return t.isDefault
}

func (t *Tariff) IsActive() bool {
	return t.isActive
}

// CanMakeDefault Тарифом по умолчанию становится только активный: отключённый к новой стоянке не применяется
func (t *Tariff) CanMakeDefault() error {
	if !t.isActive {
		return ErrTariffInactive
	}

	return nil
}

// MakeDefault Переход «тариф по умолчанию»; недопустимый — ошибка CanMakeDefault, состояние не меняется
func (t *Tariff) MakeDefault() error {
	if err := t.CanMakeDefault(); err != nil {
		return err
	}

	t.isDefault = true

	return nil
}

// CanDeactivate Отключить можно только активный тариф; массовое отключение тарифов парковки повторяет это условием UPDATE
func (t *Tariff) CanDeactivate() error {
	if !t.isActive {
		return ErrTariffInactive
	}

	return nil
}

// UnsetDefault Прежний тариф по умолчанию теряет флаг при назначении другого; допустим всегда
func (t *Tariff) UnsetDefault() {
	t.isDefault = false
}

// PriceLimit Лимит цены парковки из внешнего реестра парковок
type PriceLimit struct {
	ParkingID uint
	// MaxPrice Верхняя граница цены часа в минимальных единицах валюты тарифа
	MaxPrice uint
}
