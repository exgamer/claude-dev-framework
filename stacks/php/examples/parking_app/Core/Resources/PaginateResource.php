<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Ответ списка формы (1) пагинации, P-19: результат PaginatedQueryHelper (items + pagination Laravel-пагинатора + params) как есть; эталон модуля — форма (2), PaginatedResource.
 * Схемы — Search*: имя PaginationMeta занято формой (2).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(
    schema: 'SearchPaginationLink',
    properties: [
        new OA\Property(property: 'url', type: 'string', example: 'https://example.com/parking/admin/tariffs?page=1', nullable: true),
        new OA\Property(property: 'label', type: 'string', example: '1'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SearchPaginationMeta',
    required: ['current_page', 'per_page', 'total', 'last_page'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'first_page_url', type: 'string', nullable: true),
        new OA\Property(property: 'last_page_url', type: 'string', nullable: true),
        new OA\Property(property: 'next_page_url', type: 'string', nullable: true),
        new OA\Property(property: 'prev_page_url', type: 'string', nullable: true),
        new OA\Property(property: 'path', type: 'string', example: 'https://example.com/parking/admin/tariffs'),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'to', type: 'integer', example: 30, nullable: true),
        new OA\Property(property: 'per_page', type: 'integer', example: 30),
        new OA\Property(property: 'total', type: 'integer', example: 134),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SearchPaginationLink'),
        ),
    ],
    type: 'object'
)]
class PaginateResource extends JsonResource
{
}
