<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает routes.php партнёрского API Billing автозагрузкой ModuleServiceProvider; middleware — в самих маршрутах.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ServiceProvider extends CoreServiceProvider
{
}
