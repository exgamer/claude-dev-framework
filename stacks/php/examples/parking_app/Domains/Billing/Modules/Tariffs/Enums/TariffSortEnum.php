<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;
use OpenApi\Attributes as OA;

/**
 * Сортировка списка тарифов: минус — по убыванию.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(schema: 'TariffSortEnum', type: 'string', enum: [
    TariffSortEnum::NAME_ASC->value,
    TariffSortEnum::NAME_DESC->value,
    TariffSortEnum::CREATED_AT_ASC->value,
    TariffSortEnum::CREATED_AT_DESC->value,
])]
enum TariffSortEnum: string implements Enumerable
{
    use EnumerableTrait;

    case NAME_ASC = 'name';
    case NAME_DESC = '-name';
    case CREATED_AT_ASC = 'created_at';
    case CREATED_AT_DESC = '-created_at';
}
