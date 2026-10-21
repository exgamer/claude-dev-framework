<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffEventStatusRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Repositories\OutboxTariffEventRepository;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories\TariffRepository;

return [
    [
        'abstract' => TariffRepositoryInterface::class,
        'concrete' => TariffRepository::class,
    ],
    [
        'abstract' => OutboxTariffEventRepositoryInterface::class,
        'concrete' => OutboxTariffEventRepository::class,
    ],
    // O-6: интерфейс модуля Tariffs — реализация соседнего модуля OutboxTariffEvents
    [
        'abstract' => TariffEventStatusRepositoryInterface::class,
        'concrete' => OutboxTariffEventRepository::class,
    ],
];
