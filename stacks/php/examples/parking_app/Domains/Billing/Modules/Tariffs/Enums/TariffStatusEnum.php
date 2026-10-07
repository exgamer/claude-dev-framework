<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums;

/**
 * Статус тарифа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum TariffStatusEnum: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
