<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use App\ParkingApp\Infrastructure\Http\CityParking\PriceLimits\CityParkingPriceLimitsRepository;
use App\ParkingApp\Infrastructure\Http\CityParking\PriceLimits\Mocks\CityParkingPriceLimitsRepositoryMock;

return [
    [
        'abstract' => PriceLimitRepositoryInterface::class,
        'concrete' => CityParkingPriceLimitsRepository::class,
        'mock' => CityParkingPriceLimitsRepositoryMock::class,
    ],
];
