package auth

import (
	admindomain "example.com/parking-service/internal/domains/identity/admin"
	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
)

func newServicesFactory(
	repositoriesFactory *repositoriesFactory,
	adminSettings admindomain.AuthSettings,
	partnerSettings partnerdomain.AuthSettings,
) *servicesFactory {
	return &servicesFactory{
		AdminService: admindomain.NewService(
			repositoriesFactory.TokenVerifier,
			repositoriesFactory.AdminRepository,
			repositoriesFactory.AdminCacheRepository,
			adminSettings,
		),
		PartnerService: partnerdomain.NewService(
			repositoriesFactory.PartnerNonceRepository,
			partnerSettings,
		),
	}
}

type servicesFactory struct {
	AdminService   *admindomain.Service
	PartnerService *partnerdomain.Service
}
