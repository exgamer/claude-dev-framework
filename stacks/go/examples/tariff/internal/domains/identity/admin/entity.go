package admin

import "time"

// Admin Администратор с правами на момент загрузки из БД
type Admin struct {
	ID uint
	// SystemRoles Системные роли (super-admin)
	SystemRoles []string
	// Permissions Имена прав guard'а admin-api (parking.admin.sessions.view и т.п.)
	Permissions []string
	// AllowedParkingIDs Парковки, доступные через права building.<key>
	AllowedParkingIDs []uint
	// RevokedAt Доступ отозван; такой администратор не аутентифицируется
	RevokedAt *time.Time
}
