package tariffs

// tariff Тариф в запросе партнёрского биллинга
type tariff struct {
	ID        uint   `json:"id"`
	ParkingID uint   `json:"parking_id"`
	Name      string `json:"name"`
	Currency  string `json:"currency"`
	Price     uint   `json:"price"`
}
