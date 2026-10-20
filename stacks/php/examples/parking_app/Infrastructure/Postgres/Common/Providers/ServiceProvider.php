<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Common\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает миграции схемы parking и функции on_update_current_timestamp и definitions.php менеджера транзакций.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
