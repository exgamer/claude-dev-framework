<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Common\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;
use OpenApi\Attributes as OA;

/**
 * Статус доставки события о тарифе в RabbitMQ.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(schema: 'OutboxTariffEventStatusEnum', type: 'string', enum: [
    OutboxTariffEventStatusEnum::PENDING->value,
    OutboxTariffEventStatusEnum::PUBLISHED->value,
    OutboxTariffEventStatusEnum::FAILED->value,
    OutboxTariffEventStatusEnum::AWAITING_RETRY->value,
])]
enum OutboxTariffEventStatusEnum: string implements Enumerable
{
    use EnumerableTrait;

    /** Записано в транзакции сценария, ждёт первой отправки */
    case PENDING = 'pending';

    /** RabbitMQ принял событие */
    case PUBLISHED = 'published';

    /** Попытки исчерпаны — ручной разбор: алерт — исключение в report() (трекер), статус виден в GET tariffs/default */
    case FAILED = 'failed';

    /** Отправка не удалась; повторит консольная команда по расписанию */
    case AWAITING_RETRY = 'awaiting_retry';

    /**
     * Статусы, из которых событие ещё отправляется; опубликованное и исчерпавшее попытки — нет.
     *
     * @return list<self>
     */
    public static function deliverable(): array
    {
        return [self::PENDING, self::AWAITING_RETRY];
    }

    /**
     * @return list<string>
     */
    public static function deliverableValues(): array
    {
        $values = [];

        foreach (self::deliverable() as $status) {
            $values[] = $status->value;
        }

        return $values;
    }

    public function isDeliverable(): bool
    {
        return in_array($this, self::deliverable(), true);
    }
}
