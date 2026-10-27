<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Console\Billing;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает commands.php Billing (тариф по умолчанию, повтор outbox, активация наступивших) автозагрузкой ModuleServiceProvider.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ServiceProvider extends CoreServiceProvider
{
}
