<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TariffPaginatedData',
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/TariffResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
        new OA\Property(property: 'params', ref: '#/components/schemas/ParamsSchema'),
    ],
    type: 'object'
)]
class TariffPaginatedResponse extends JsonResource
{
}
