<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\CityParking\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php: пределы цен городского реестра парковок (CityParking) по HTTP или мок за интерфейсом домена.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
