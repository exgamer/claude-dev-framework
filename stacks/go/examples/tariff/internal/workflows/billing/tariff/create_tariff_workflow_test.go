package tariff

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	parkingdomain "example.com/parking-service/internal/domains/handbook/parking"
	"example.com/parking-service/internal/testsupport/billingfakes"
	"example.com/parking-service/internal/testsupport/handbookfakes"
)

func TestCreateTariffWorkflow_ParkingNotFound(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository()
	workflow := NewCreateTariffWorkflow(
		handbookfakes.NewParkingRepository(),
		tariffdomain.NewService(tariffs, billingfakes.NewTariffCacheRepository()),
	)

	_, err := workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 42, Name: "Стандарт"})

	require.Error(t, err)
	assert.Empty(t, tariffs.Items)
}

func TestCreateTariffWorkflow_CreatesNotDefault(t *testing.T) {
	parkings := handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: true})
	tariffs := billingfakes.NewTariffRepository()
	workflow := NewCreateTariffWorkflow(parkings, tariffdomain.NewService(tariffs, billingfakes.NewTariffCacheRepository()))

	tariff, err := workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT"})

	require.NoError(t, err)
	assert.Equal(t, uint(1), tariff.ParkingID)
	assert.False(t, tariff.IsDefault)
}
