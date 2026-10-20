package admin

import "time"

// admin Администратор (public.admins)
type admin struct {
	ID uint `gorm:"column:id;primaryKey"`
	// SystemRoles jsonb-массив системных ролей
	SystemRoles []byte     `gorm:"column:system_roles"`
	RevokedAt   *time.Time `gorm:"column:revoked_at"`
}

func (admin) TableName() string {
	return "public.admins"
}
