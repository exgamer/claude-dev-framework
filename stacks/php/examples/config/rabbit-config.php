<?php

declare(strict_types=1);

// Фрагмент config/rabbit-config.php хоста (подключается в config/app.php как 'rabbit-config'): топология модуля тарифов,
// ключи — домен.модуль.действие
return [
    'billing' => [
        // Публикация событий о тарифах: topic exchange, routing key — тип события (tariff.created)
        'tariff-events' => [
            'exchange' => 'parking.billing.tariff-events.exchange',
        ],
        // Подписка на справочник парковок: тарифы отключённой парковки — в архив
        'parking-deactivated' => [
            'exchange' => 'parking.handbook.exchange',
            'routing-key' => 'handbook.parking.deactivated',
            'queue-name' => 'parking.billing.parking-deactivated',
        ],
    ],
];
