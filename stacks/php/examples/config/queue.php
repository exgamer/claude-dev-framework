<?php

declare(strict_types=1);

// Фрагмент config/queue.php хоста: обработчики RabbitMQ — в connections.rabbitmq.handler_map

use App\ParkingApp\Entrypoints\Queue\Billing\Tariffs\ParkingDeactivatedHandler;

return [
    'connections' => [
        'rabbitmq' => [
            'handler_map' => [
                // handbook.parking.deactivated: временная ошибка — повтор через 1, 5, 15, 60 минут, потом failed_jobs
                [
                    'queue' => config('app.rabbit-config.billing.parking-deactivated.queue-name'),
                    'exchange' => config('app.rabbit-config.billing.parking-deactivated.exchange'),
                    'routing_key' => config('app.rabbit-config.billing.parking-deactivated.routing-key'),
                    'handler' => ParkingDeactivatedHandler::class,
                    'durability' => [
                        'delayed' => [60, 300, 900, 3600],
                    ],
                ],
            ],
        ],
    ],
];
