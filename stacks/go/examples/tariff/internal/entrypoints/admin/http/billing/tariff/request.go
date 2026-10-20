package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin/validation"
)

// indexRequest Фильтр списка тарифов
type indexRequest struct {
	validation.Request
	ParkingID uint `form:"parking_id" binding:"omitempty,gt=0"`
	Page      uint `form:"page" binding:"omitempty,lte=10000"`
	PerPage   uint `form:"per_page" binding:"omitempty,lte=100"`
}

// defaultRequest Тариф по умолчанию парковки
type defaultRequest struct {
	validation.Request
	ParkingID uint `form:"parking_id" binding:"required,gt=0"`
}

// createRequest Создание тарифа
type createRequest struct {
	validation.Request
	ParkingID    uint   `json:"parking_id" binding:"required,gt=0"`
	Name         string `json:"name" binding:"required,max=255"`
	Currency     string `json:"currency" binding:"required,oneof=KZT RUB USD"`
	GraceMinutes uint   `json:"grace_minutes" binding:"lte=1440"`
	Price        uint   `json:"price" binding:"required,gt=0,lte=100000000"`
}

// updateRequest Частичное обновление тарифа: отсутствующее поле не меняется
type updateRequest struct {
	validation.Request
	Name         *string `json:"name" binding:"omitempty,max=255"`
	GraceMinutes *uint   `json:"grace_minutes" binding:"omitempty,lte=1440"`
}
