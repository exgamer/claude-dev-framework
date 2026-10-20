<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\PartnerBilling\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php: уведомление партнёрского биллинга (PartnerBilling) о тарифах по HTTP или мок за интерфейсом домена.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends CoreServiceProvider
{
}
