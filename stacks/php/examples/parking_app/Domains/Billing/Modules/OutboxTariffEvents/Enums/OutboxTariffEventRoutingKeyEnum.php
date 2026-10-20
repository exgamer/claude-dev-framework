<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Тип события о тарифе — он же routing key публикации.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum OutboxTariffEventRoutingKeyEnum: string implements Enumerable
{
    use EnumerableTrait;

    case TARIFF_CREATED = 'tariff.created';
}
