<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Статус доставки события о тарифе в RabbitMQ.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum OutboxTariffEventStatusEnum: string implements Enumerable
{
    use EnumerableTrait;

    /** Записано в транзакции сценария, ждёт первой отправки */
    case PENDING = 'pending';

    /** RabbitMQ принял событие */
    case PUBLISHED = 'published';

    /** Попытки исчерпаны — ручной разбор */
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
