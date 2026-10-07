<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudService;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;

return [
    [
        'abstract' => TariffCrudServiceInterface::class,
        'concrete' => TariffCrudService::class,
    ],
];
