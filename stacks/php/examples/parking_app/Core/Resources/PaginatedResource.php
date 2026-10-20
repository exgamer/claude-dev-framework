<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Форма постраничного ответа подпроекта: items + pagination. Наследник задаёт ресурс элемента.
 *
 * @property LengthAwarePaginator $resource
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'total', description: 'Всего записей', type: 'integer', example: 42),
        new OA\Property(property: 'per_page', description: 'Записей на странице', type: 'integer', example: 30),
        new OA\Property(property: 'current_page', description: 'Текущая страница', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', description: 'Последняя страница', type: 'integer', example: 2),
    ],
    type: 'object'
)]
abstract class PaginatedResource extends JsonResource
{
    /**
     * @return class-string<JsonResource>
     */
    abstract protected function itemResource(): string;

    /**
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $itemResource = $this->itemResource();

        return [
            'items' => $itemResource::collection($this->resource->items()),
            'pagination' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
            ],
        ];
    }
}
