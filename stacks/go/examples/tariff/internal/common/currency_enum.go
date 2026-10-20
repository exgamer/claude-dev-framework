package common

// Currency Валюта цены (ISO 4217); общая для тарифов billing и парковок handbook
type Currency string

const (
	CurrencyKZT Currency = "KZT"
	CurrencyRUB Currency = "RUB"
	CurrencyUSD Currency = "USD"
)

// IsValid Валюта из поддерживаемых
func (c Currency) IsValid() bool {
	switch c {
	case CurrencyKZT, CurrencyRUB, CurrencyUSD:
		return true
	default:
		return false
	}
}
