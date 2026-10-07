package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin/validation"
)

// indexRequest Фильтр списка тарифов
type indexRequest struct {
	validation.Request
	ParkingID uint `form:"parking_id" validate:"omitempty,gt=0"`
	Page      uint `form:"page"`
	PerPage   uint `form:"per_page" validate:"omitempty,lte=100"`
}

// createRequest Создание тарифа
type createRequest struct {
	validation.Request
	ParkingID    uint   `json:"parking_id" binding:"required" validate:"required,gt=0"`
	Name         string `json:"name" binding:"required" validate:"required,max=255"`
	Currency     string `json:"currency" binding:"required" validate:"required,len=3"`
	GraceMinutes uint   `json:"grace_minutes" validate:"lte=1440"`
}

// updateRequest Частичное обновление тарифа: отсутствующее поле не меняется
type updateRequest struct {
	validation.Request
	Name         *string `json:"name" validate:"omitempty,max=255"`
	GraceMinutes *uint   `json:"grace_minutes" validate:"omitempty,lte=1440"`
}
