package tariff

import (
	"fmt"
	"strings"
)

// MaxGraceMinutes Доп. время на выезд — не больше суток
const MaxGraceMinutes = 1440

// ValidateTariff Инварианты нового тарифа без данных хранилища; ошибки по полям, пусто — тариф корректен.
// Проверки по данным (парковка, её валюта, лимит цены) — у вызывающего через репозитории
func ValidateTariff(tariff *Tariff) map[string]any {
	fieldErrors := map[string]any{}

	if strings.TrimSpace(tariff.Name) == "" {
		fieldErrors["name"] = "название обязательно"
	}

	if !tariff.Currency.IsValid() {
		fieldErrors["currency"] = "валюта не поддерживается"
	}

	if tariff.Price == 0 {
		fieldErrors["price"] = "цена должна быть больше нуля"
	}

	if tariff.GraceMinutes > MaxGraceMinutes {
		fieldErrors["grace_minutes"] = fmt.Sprintf("доп. время не больше %d минут", MaxGraceMinutes)
	}

	return fieldErrors
}

// ValidatePatch Инварианты частичного обновления: проверяются только переданные поля
func ValidatePatch(patch *Patch) map[string]any {
	fieldErrors := map[string]any{}

	if patch.Name != nil && strings.TrimSpace(*patch.Name) == "" {
		fieldErrors["name"] = "название не может быть пустым"
	}

	if patch.GraceMinutes != nil && *patch.GraceMinutes > MaxGraceMinutes {
		fieldErrors["grace_minutes"] = fmt.Sprintf("доп. время не больше %d минут", MaxGraceMinutes)
	}

	return fieldErrors
}
