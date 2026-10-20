<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses;

use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Тариф в ответе админки: enum — значением, даты — ISO 8601.
 *
 * @property Tariff $resource
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(
    schema: 'TariffResponse',
    title: 'Tariff',
    description: 'Тариф',
    properties: [
        new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/CurrencyEnum'),
        new OA\Property(property: 'price', description: 'Цена часа в минимальных единицах валюты', type: 'integer', example: 50000),
        new OA\Property(property: 'is_default', description: 'Тариф по умолчанию', type: 'boolean', example: true),
        new OA\Property(property: 'status', ref: '#/components/schemas/TariffStatusEnum'),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'scheduled_at', description: 'Дата активации, ISO 8601; у действующего и архивного — null', type: 'string', format: 'date-time', example: '2026-11-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'created_at', description: 'Дата создания, ISO 8601', type: 'string', format: 'date-time', example: '2026-10-20T09:30:00.000000Z'),
        new OA\Property(property: 'updated_at', description: 'Дата обновления, ISO 8601; null — не обновлялся', type: 'string', format: 'date-time', example: '2026-10-20T09:30:00.000000Z', nullable: true),
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
            'name' => $this->resource->name,
            'currency' => $this->resource->currency->value,
            'price' => $this->resource->price,
            'is_default' => $this->resource->is_default,
            'status' => $this->resource->status->value,
            'grace_minutes' => $this->resource->grace_minutes,
            'scheduled_at' => $this->resource->scheduled_at?->toJSON(),
            'created_at' => $this->resource->created_at->toJSON(),
            'updated_at' => $this->resource->updated_at?->toJSON(),
        ];
    }
}
