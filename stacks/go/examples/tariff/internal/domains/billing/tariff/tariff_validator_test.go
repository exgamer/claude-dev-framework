package tariff_test

import (
	"testing"

	"github.com/stretchr/testify/assert"

	"example.com/parking-service/internal/common"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func TestValidateTariff_ValidHasNoErrors(t *testing.T) {
	fieldErrors := tariffdomain.ValidateTariff(&tariffdomain.Tariff{
		ParkingID:    1,
		Name:         "Стандарт",
		Currency:     common.CurrencyKZT,
		GraceMinutes: tariffdomain.MaxGraceMinutes,
		Price:        100,
	})

	assert.Empty(t, fieldErrors)
}

func TestValidateTariff_ErrorsByField(t *testing.T) {
	fieldErrors := tariffdomain.ValidateTariff(&tariffdomain.Tariff{
		ParkingID:    1,
		Name:         "  ",
		Currency:     "EUR",
		GraceMinutes: tariffdomain.MaxGraceMinutes + 1,
	})

	assert.Len(t, fieldErrors, 4)
	assert.Contains(t, fieldErrors, "name")
	assert.Contains(t, fieldErrors, "currency")
	assert.Contains(t, fieldErrors, "price")
	assert.Contains(t, fieldErrors, "grace_minutes")
}

func TestValidatePatch_ChecksOnlyPassedFields(t *testing.T) {
	blank := " "
	tooLong := uint(tariffdomain.MaxGraceMinutes + 1)

	assert.Empty(t, tariffdomain.ValidatePatch(&tariffdomain.Patch{}))

	fieldErrors := tariffdomain.ValidatePatch(&tariffdomain.Patch{Name: &blank, GraceMinutes: &tooLong})

	assert.Contains(t, fieldErrors, "name")
	assert.Contains(t, fieldErrors, "grace_minutes")
}
