<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Console\Billing;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает commands.php Billing (тариф по умолчанию, повтор outbox) автозагрузкой ModuleServiceProvider.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
