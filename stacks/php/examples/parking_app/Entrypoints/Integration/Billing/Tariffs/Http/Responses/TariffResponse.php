<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Responses;

use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Тариф для партнёра: условия для расчёта стоимости, без служебных дат.
 *
 * @property Tariff $resource
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(
    schema: 'IntegrationTariffResponse',
    title: 'IntegrationTariff',
    description: 'Тариф для партнёра',
    properties: [
        new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'currency', ref: '#/components/schemas/CurrencyEnum'),
        new OA\Property(property: 'price', description: 'Цена часа в минимальных единицах валюты', type: 'integer', example: 50000),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'is_default', description: 'Тариф по умолчанию', type: 'boolean', example: true),
        new OA\Property(property: 'status', ref: '#/components/schemas/TariffStatusEnum'),
    ],
    type: 'object'
)]
class TariffResponse extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'parking_id' => $this->resource->parking_id,
            'currency' => $this->resource->currency->value,
            'price' => $this->resource->price,
            'grace_minutes' => $this->resource->grace_minutes,
            'is_default' => $this->resource->is_default,
            'status' => $this->resource->status->value,
        ];
    }
}
