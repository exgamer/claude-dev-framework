<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories\TariffRepository;

return [
    [
        'abstract' => TariffRepositoryInterface::class,
        'concrete' => TariffRepository::class,
    ],
];
