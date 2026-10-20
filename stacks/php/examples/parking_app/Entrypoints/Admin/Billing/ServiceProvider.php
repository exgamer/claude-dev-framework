<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает routes.php админки Billing автозагрузкой ModuleServiceProvider; middleware и права — в самих маршрутах.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
