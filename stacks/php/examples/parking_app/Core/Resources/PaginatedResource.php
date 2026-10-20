<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Форма постраничного ответа подпроекта: items + pagination (схема PaginationMeta), как у Go SDK. Наследник задаёт ресурс элемента.
 *
 * @property LengthAwarePaginator $resource
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
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
            'pagination' => PaginationMeta::fromPaginator($this->resource),
        ];
    }
}
