<?php

declare(strict_types=1);

use App\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs\Mocks\PartnerBillingTariffsNotifierMock;
use App\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs\PartnerBillingTariffsNotifier;
use App\ParkingApp\Workflows\Billing\Tariffs\TariffNotifierInterface;

return [
    [
        'abstract' => TariffNotifierInterface::class,
        'concrete' => PartnerBillingTariffsNotifier::class,
        'mock' => PartnerBillingTariffsNotifierMock::class,
    ],
];
