<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\RabbitMQ\Billing\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php: публикатор outbox-событий о тарифах в RabbitMQ (или его мок) за интерфейсом домена.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ServiceProvider extends CoreServiceProvider
{
}
