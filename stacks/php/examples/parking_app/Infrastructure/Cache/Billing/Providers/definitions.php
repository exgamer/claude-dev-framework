<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Infrastructure\Cache\Billing\Tariffs\Repositories\TariffCacheRepository;

return [
    [
        'abstract' => TariffCacheRepositoryInterface::class,
        'concrete' => TariffCacheRepository::class,
    ],
];
