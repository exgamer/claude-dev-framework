<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses;

use App\ParkingApp\Core\Resources\PaginatedResource;
use OpenApi\Attributes as OA;

/**
 * Страница списка тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(
    schema: 'TariffPaginatedResponse',
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TariffResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
    ],
    type: 'object'
)]
class TariffPaginatedResponse extends PaginatedResource
{
    protected function itemResource(): string
    {
        return TariffResponse::class;
    }
}
