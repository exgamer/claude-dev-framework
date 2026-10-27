<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php (репозитории тарифов и outbox → Postgres) и миграции таблиц Billing.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ServiceProvider extends CoreServiceProvider
{
}
