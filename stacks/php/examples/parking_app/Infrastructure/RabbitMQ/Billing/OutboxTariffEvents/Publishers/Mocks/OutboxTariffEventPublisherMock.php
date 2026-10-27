<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers\Mocks;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;

/**
 * Публикация без брокера для тестов: событие считается принятым.
 *
 * @author Имя Фамилия <name@example.com>
 */
class OutboxTariffEventPublisherMock implements OutboxTariffEventPublisherInterface
{
    public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void
    {
    }
}
