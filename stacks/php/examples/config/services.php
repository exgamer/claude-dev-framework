<?php

declare(strict_types=1);

// Фрагмент config/services.php приложения: ключи внешних систем, значения — только из env
return [
    'city_parking' => [
        'base_url' => env('CITY_PARKING_BASE_URL'),
        'timeout' => env('CITY_PARKING_TIMEOUT'),
    ],

    'partner_billing' => [
        'base_url' => env('PARTNER_BILLING_BASE_URL'),
        'timeout' => env('PARTNER_BILLING_TIMEOUT'),
    ],
];
