package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"

	"example.com/parking-service/internal/common"
	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func indexRequestToSearch(request indexRequest, scope tariffdomain.ParkingScope) *tariffdomain.Search {
	return &tariffdomain.Search{
		ParkingID: request.ParkingID,
		Scope:     scope,
		Page:      request.Page,
		PerPage:   request.PerPage,
	}
}

func createRequestToParams(request createRequest) *tariffworkflow.CreateTariffParams {
	return &tariffworkflow.CreateTariffParams{
		ParkingID:    request.ParkingID,
		Name:         request.Name,
		Currency:     common.Currency(request.Currency),
		GraceMinutes: request.GraceMinutes,
		Price:        request.Price,
	}
}

func updateRequestToPatch(request updateRequest) *tariffdomain.Patch {
	return &tariffdomain.Patch{
		Name:         request.Name,
		GraceMinutes: request.GraceMinutes,
	}
}

func tariffToData(tariff *tariffdomain.Tariff) *tariffData {
	if tariff == nil {
		return nil
	}

	return &tariffData{
		ID:           tariff.ID,
		ParkingID:    tariff.ParkingID,
		Name:         tariff.Name,
		Currency:     string(tariff.Currency),
		IsDefault:    tariff.IsDefault,
		IsActive:     tariff.IsActive,
		GraceMinutes: tariff.GraceMinutes,
		Price:        tariff.Price,
	}
}

func paginatedToData(paginated *pagination.Paginated[tariffdomain.Tariff]) *pagination.Paginated[tariffData] {
	if paginated == nil {
		return nil
	}

	items := make([]*tariffData, 0, len(paginated.Items))

	for _, tariff := range paginated.Items {
		items = append(items, tariffToData(tariff))
	}

	return &pagination.Paginated[tariffData]{
		Items:      items,
		Pagination: paginated.Pagination,
	}
}
