<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Cache\Common\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php: кеш nonce подписи партнёрских запросов (защита от повтора).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
