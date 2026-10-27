package admin

import "time"

// admin Администратор в кеше; имена полей без тегов совпадают с прежним форматом (сущность целиком), записи до выкладки читаются
type admin struct {
	ID                uint
	SystemRoles       []string
	Permissions       []string
	AllowedParkingIDs []uint
	RevokedAt         *time.Time
}
