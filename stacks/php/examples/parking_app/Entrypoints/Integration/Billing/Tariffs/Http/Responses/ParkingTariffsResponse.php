<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Responses;

use App\ParkingApp\Workflows\Billing\Tariffs\DTO\ParkingTariffsDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Парковка и её действующие тарифы для партнёра.
 *
 * @property ParkingTariffsDto $resource
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(
    schema: 'ParkingTariffsResponse',
    title: 'ParkingTariffs',
    description: 'Парковка и её действующие тарифы',
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'parking_name', description: 'Название парковки', type: 'string', example: 'ТРЦ, уровень -1'),
        new OA\Property(
            property: 'tariffs',
            description: 'Действующие тарифы, первым — тариф по умолчанию',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
                    new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
                    new OA\Property(property: 'currency', ref: '#/components/schemas/CurrencyEnum'),
                    new OA\Property(property: 'price', description: 'Цена часа в минимальных единицах валюты', type: 'integer', example: 50000),
                    new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
                    new OA\Property(property: 'is_default', description: 'Тариф по умолчанию', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
    ],
    type: 'object'
)]
class ParkingTariffsResponse extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tariffs = [];

        foreach ($this->resource->getTariffs() as $tariff) {
            $tariffs[] = [
                'id' => $tariff->getId(),
                'name' => $tariff->getName(),
                'currency' => $tariff->getCurrency()->value,
                'price' => $tariff->getPrice(),
                'grace_minutes' => $tariff->getGraceMinutes(),
                'is_default' => $tariff->isDefault(),
            ];
        }

        return [
            'parking_id' => $this->resource->getParkingId(),
            'parking_name' => $this->resource->getParkingName(),
            'tariffs' => $tariffs,
        ];
    }
}
