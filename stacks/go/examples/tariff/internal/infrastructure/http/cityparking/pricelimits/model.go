package pricelimits

// priceLimit Лимит цены парковки в ответе реестра
type priceLimit struct {
	ParkingID uint `json:"parking_id"`
	MaxPrice  uint `json:"max_price"`
}
