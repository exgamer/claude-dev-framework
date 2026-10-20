<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing;

use App\ParkingApp\Core\Providers\ServiceProvider as Base;

/**
 * Подключает definitions.php домена (интерфейс сервиса → реализация) автозагрузкой ModuleServiceProvider; своей логики нет.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends Base
{
}
