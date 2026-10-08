<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Статус тарифа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum TariffStatusEnum: string implements Enumerable
{
    use EnumerableTrait;

    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
