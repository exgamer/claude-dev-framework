package partner

import (
	"context"
	"crypto/subtle"
)

func NewService(nonceRepository NonceRepository, settings AuthSettings) *Service {
	return &Service{
		nonceRepository: nonceRepository,
		settings:        settings,
	}
}

// Service Аутентификация машинного вызова партнёра: ключ API и защита подписанного запроса от повтора
type Service struct {
	nonceRepository NonceRepository
	settings        AuthSettings
}

// AuthenticateAPIKey Ключ совпадает с ключом из конфига; сравнение — за постоянное время, пустой ключ не принимается
func (s *Service) AuthenticateAPIKey(key string) error {
	if key == "" || s.settings.APIKey == "" || subtle.ConstantTimeCompare([]byte(key), []byte(s.settings.APIKey)) != 1 {
		return ErrInvalidAPIKey
	}

	return nil
}

// ConsumeNonce Занимает nonce уже проверенной подписи на NonceTTL; встречался — ErrNonceReplayed, прочая ошибка — сбой хранилища
func (s *Service) ConsumeNonce(ctx context.Context, nonce string) error {
	fresh, err := s.nonceRepository.Remember(ctx, nonce, s.settings.NonceTTL)
	if err != nil {
		return err
	}

	if !fresh {
		return ErrNonceReplayed
	}

	return nil
}
