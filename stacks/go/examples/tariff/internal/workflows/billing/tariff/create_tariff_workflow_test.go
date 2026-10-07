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
	"example.com/parking-service/internal/testsupport/txfakes"
)

func newWorkflow(parkings *handbookfakes.ParkingRepository, tariffs *billingfakes.TariffRepository) *CreateTariffWorkflow {
	return NewCreateTariffWorkflow(txfakes.NewManager2[parkingdomain.Repository, tariffdomain.Repository](parkings, tariffs))
}

func TestCreateTariffWorkflow_ParkingNotFound(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository()
	workflow := newWorkflow(handbookfakes.NewParkingRepository(), tariffs)

	_, err := workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 42, Name: "Стандарт"})

	require.Error(t, err)
	assert.Empty(t, tariffs.Items)
}

func TestCreateTariffWorkflow_ParkingDisabled(t *testing.T) {
	tariffs := billingfakes.NewTariffRepository()
	workflow := newWorkflow(handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: false}), tariffs)

	_, err := workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт"})

	require.Error(t, err)
	assert.Empty(t, tariffs.Items)
}

func TestCreateTariffWorkflow_CreatesNotDefault(t *testing.T) {
	parkings := handbookfakes.NewParkingRepository(&parkingdomain.Parking{ID: 1, IsActive: true})
	tariffs := billingfakes.NewTariffRepository()
	workflow := newWorkflow(parkings, tariffs)

	tariff, err := workflow.Exec(context.Background(), &CreateTariffParams{ParkingID: 1, Name: "Стандарт", Currency: "KZT"})

	require.NoError(t, err)
	assert.Equal(t, uint(1), tariff.ParkingID)
	assert.False(t, tariff.IsDefault)
}
