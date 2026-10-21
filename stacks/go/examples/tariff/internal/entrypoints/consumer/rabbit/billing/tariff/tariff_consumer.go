package tariff

import (
	"context"
	"encoding/json"
	"fmt"
	"strconv"

	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"
	"github.com/ThreeDotsLabs/watermill/message"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

// deathCountMetadataKey Сколько раз сообщение вернулось из retry-очереди; кладёт marshaler.DeathTolerantMarshaler SDK
const deathCountMetadataKey = "x-death-count"

// parkingDeactivatedMessage Событие справочника handbook.parking.deactivated
type parkingDeactivatedMessage struct {
	ParkingID uint `json:"parking_id"`
}

func NewConsumer(deactivateParkingTariffsCommand *tariffdomain.DeactivateParkingTariffsCommand, maxAttempts uint) *Consumer {
	return &Consumer{
		deactivateParkingTariffsCommand: deactivateParkingTariffsCommand,
		maxAttempts:                     maxAttempts,
	}
}

type Consumer struct {
	deactivateParkingTariffsCommand *tariffdomain.DeactivateParkingTariffsCommand
	maxAttempts                     uint
}

// Consume Парковку отключили в справочнике — отключить её тарифы.
// Битый payload повтором не исправить — в трекер и ACK; временная ошибка — NACK (retry-очередь), на последней попытке — в трекер и ACK
func (c *Consumer) Consume(ctx context.Context, msg *message.Message) error {
	var payload parkingDeactivatedMessage

	tags := map[string]string{"component": "tariff_consumer"}

	if err := json.Unmarshal(msg.Payload, &payload); err != nil {
		decodeErr := fmt.Errorf("decode handbook.parking.deactivated failed (message_uuid=%s): %w", msg.UUID, err)
		errorreporter.CaptureError(ctx, decodeErr, tags)

		return nil
	}

	if payload.ParkingID == 0 {
		payloadErr := fmt.Errorf("handbook.parking.deactivated without parking_id (message_uuid=%s)", msg.UUID)
		errorreporter.CaptureError(ctx, payloadErr, tags)

		return nil
	}

	err := c.deactivateParkingTariffsCommand.Exec(ctx, payload.ParkingID)
	if err == nil {
		return nil
	}

	attempt := deliveryAttempt(msg)
	if attempt < c.maxAttempts {
		return err
	}

	exhaustedErr := fmt.Errorf("deactivate tariffs of parking %d failed after %d attempts (message_uuid=%s): %w", payload.ParkingID, attempt, msg.UUID, err)
	errorreporter.CaptureError(ctx, exhaustedErr, tags)

	return nil
}

// deliveryAttempt Номер текущей доставки: возвраты из retry-очереди + 1; без заголовка — первая
func deliveryAttempt(msg *message.Message) uint {
	returns, err := strconv.ParseUint(msg.Metadata.Get(deathCountMetadataKey), 10, 32)
	if err != nil {
		return 1
	}

	return uint(returns) + 1
}
