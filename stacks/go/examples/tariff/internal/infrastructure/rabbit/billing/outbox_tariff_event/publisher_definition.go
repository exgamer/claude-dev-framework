package outbox_tariff_event

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/config"
)

const (
	// PublisherName Имя издателя событий тарифов в реестре SDK
	PublisherName = "billing-tariff-events"
	// exchangeName Topic exchange событий биллинга; routing key — RoutingKey события
	exchangeName = "billing"
)

// NewPublisherDefinition Издатель событий тарифов: durable topic exchange, persistent-сообщения, подтверждение брокера
func NewPublisherDefinition() config.PublisherDefinition {
	return config.NewPublisherDefinition(PublisherName, config.NewPublisherTopicDurableConfig(exchangeName)...)
}
