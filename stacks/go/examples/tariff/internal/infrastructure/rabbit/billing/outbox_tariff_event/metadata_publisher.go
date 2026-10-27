package outbox_tariff_event

// metadataPublisher Публикация с метаданными
type metadataPublisher interface {
	PublishWithMetaData(topic string, meta map[string]string, payload any) error
}
