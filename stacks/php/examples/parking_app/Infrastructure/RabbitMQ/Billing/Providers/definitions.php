<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers\Mocks\OutboxTariffEventPublisherMock;
use App\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers\OutboxTariffEventPublisher;

return [
    [
        'abstract' => OutboxTariffEventPublisherInterface::class,
        'concrete' => OutboxTariffEventPublisher::class,
        'mock' => OutboxTariffEventPublisherMock::class,
    ],
];
