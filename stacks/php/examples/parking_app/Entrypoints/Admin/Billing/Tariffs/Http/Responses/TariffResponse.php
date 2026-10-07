<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TariffResponse',
    title: 'Tariff',
    description: 'Тариф',
    properties: [
        new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', description: 'Валюта', type: 'string', example: 'KZT'),
        new OA\Property(property: 'is_default', description: 'Тариф по умолчанию', type: 'boolean', example: true),
        new OA\Property(property: 'status', description: 'Статус: active, archived', type: 'string', example: 'active'),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'created_at', description: 'Дата создания', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', description: 'Дата обновления', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class TariffResponse extends JsonResource
{
}
