<?php

declare(strict_types=1);

use App\ParkingApp\Entrypoints\Console\Billing\OutboxTariffEvents\RetryOutboxTariffEventsConsoleCommand;
use App\ParkingApp\Entrypoints\Console\Billing\Tariffs\SetDefaultTariffConsoleCommand;

return [
    SetDefaultTariffConsoleCommand::class,
    RetryOutboxTariffEventsConsoleCommand::class,
];
