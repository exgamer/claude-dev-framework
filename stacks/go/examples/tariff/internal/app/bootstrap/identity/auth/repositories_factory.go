package auth

import (
	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"

	admindomain "example.com/parking-service/internal/domains/identity/admin"
	partnerdomain "example.com/parking-service/internal/domains/identity/partner"
	adminjwt "example.com/parking-service/internal/infrastructure/jwt/identity/admin"
	adminpostgres "example.com/parking-service/internal/infrastructure/postgres/identity/admin"
	adminredis "example.com/parking-service/internal/infrastructure/redis/identity/admin"
	partnerredis "example.com/parking-service/internal/infrastructure/redis/identity/partner"
)

func newRepositoriesFactory(postgresClient *gorm.DB, redisClient *redis.Client, adminConfig *adminAuthConfig) (*repositoriesFactory, error) {
	verifier, err := adminjwt.NewVerifier(adminConfig.JWTPublicKeyBase64, adminConfig.leeway())
	if err != nil {
		return nil, err
	}

	return &repositoriesFactory{
		TokenVerifier:          verifier,
		AdminRepository:        adminpostgres.NewPostgresRepository(postgresClient, adminConfig.JWTGuardName),
		AdminCacheRepository:   adminredis.NewRedisRepository(redisClient),
		PartnerNonceRepository: partnerredis.NewRedisRepository(redisClient),
	}, nil
}

type repositoriesFactory struct {
	TokenVerifier          admindomain.TokenVerifier
	AdminRepository        admindomain.Repository
	AdminCacheRepository   admindomain.CacheRepository
	PartnerNonceRepository partnerdomain.NonceRepository
}
