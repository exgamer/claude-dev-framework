<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;
use OpenApi\Attributes as OA;

/**
 * Статус тарифа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(schema: 'TariffStatusEnum', type: 'string', enum: ['active', 'scheduled', 'archived'])]
enum TariffStatusEnum: string implements Enumerable
{
    use EnumerableTrait;

    case ACTIVE = 'active';

    /** Создан с датой активации; в эту дату его активирует ActivatePlannedTariffJob и делает тарифом по умолчанию */
    case SCHEDULED = 'scheduled';

    case ARCHIVED = 'archived';

    /**
     * Статусы, которые снимает отключение парковки: действующий и ждущий активации тариф.
     *
     * @return list<self>
     */
    public static function notArchived(): array
    {
        return [self::ACTIVE, self::SCHEDULED];
    }
}
