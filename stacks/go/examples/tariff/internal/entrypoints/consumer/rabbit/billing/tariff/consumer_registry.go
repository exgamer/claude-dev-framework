package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/config"
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/enums"
	"git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/marshaler"
	amqpdriver "github.com/ThreeDotsLabs/watermill-amqp/v2/pkg/amqp"
	amqp "github.com/rabbitmq/amqp091-go"
)

const (
	// ParkingDeactivatedQueue Своя очередь сервиса; NACK уходит в retry-очередь (DLX)
	ParkingDeactivatedQueue = "billing-tariff.parking-deactivated"
	// ParkingDeactivatedRetryQueue Без читателя: по TTL сообщение возвращается в ParkingDeactivatedQueue
	ParkingDeactivatedRetryQueue = "billing-tariff.parking-deactivated.retry"
)

// GetConsumers Подписки модуля тарифов: очередь с DLX-аргументами и NoRequeueOnNack;
// маршалер SDK переносит счётчик x-death в metadata (DefaultMarshaler падает на нестроковом заголовке)
func GetConsumers(consumer *Consumer) []config.HandlerRegister {
	parkingDeactivatedConfig := config.NewConsumerByConfigs(
		amqpdriver.QueueBindConfig{
			GenerateRoutingKey: func(string) string { return "handbook.parking.deactivated" },
		},
		amqpdriver.ConsumeConfig{
			Consumer:        "billing-tariff-parking-deactivated",
			Qos:             amqpdriver.QosConfig{PrefetchCount: 10},
			NoRequeueOnNack: true,
		},
		amqpdriver.ExchangeConfig{
			GenerateName: func(string) string { return "handbook" },
			Type:         enums.TopicExchange,
			Durable:      true,
		},
		amqpdriver.QueueConfig{
			GenerateName: func(string) string { return ParkingDeactivatedQueue },
			Durable:      true,
			Arguments: amqp.Table{
				"x-dead-letter-exchange":    "",
				"x-dead-letter-routing-key": ParkingDeactivatedRetryQueue,
			},
		},
	)

	parkingDeactivatedConfig = append(parkingDeactivatedConfig, config.WithCustomMarshaler(marshaler.DeathTolerantMarshaler{}))

	return []config.HandlerRegister{
		{
			Handler: consumer.Consume,
			Config:  parkingDeactivatedConfig,
		},
	}
}
