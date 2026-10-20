package common_test

import (
	"testing"

	"github.com/stretchr/testify/assert"

	"example.com/parking-service/internal/common"
)

func TestCurrency_IsValid(t *testing.T) {
	for _, currency := range []common.Currency{common.CurrencyKZT, common.CurrencyRUB, common.CurrencyUSD} {
		assert.True(t, currency.IsValid(), currency)
	}

	for _, currency := range []common.Currency{"", "kzt", "EUR"} {
		assert.False(t, currency.IsValid(), currency)
	}
}
