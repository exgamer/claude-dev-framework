<?php

declare(strict_types=1);

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Infrastructure\Postgres\Database\Managers\DatabaseTransactionManager;

return [
    [
        'abstract' => TransactionManagerInterface::class,
        'concrete' => DatabaseTransactionManager::class,
        'mock' => NoopTransactionManager::class,
    ],
];
