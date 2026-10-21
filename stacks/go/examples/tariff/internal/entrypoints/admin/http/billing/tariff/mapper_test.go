package tariff

import (
	"testing"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// TestIndexRequestToSearch_Page page 0 или не передан — первая страница до расчёта смещения
func TestIndexRequestToSearch_Page(t *testing.T) {
	cases := []struct {
		page uint
		want uint
	}{
		{0, 1},
		{1, 1},
		{5, 5},
	}

	for _, tc := range cases {
		search := indexRequestToSearch(indexRequest{Page: tc.page}, tariffdomain.ParkingScope{All: true})

		if search.Page != tc.want {
			t.Fatalf("page %d: got %d, want %d", tc.page, search.Page, tc.want)
		}
	}
}
