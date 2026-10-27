<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\ParkingDefaultTariffDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Тариф по умолчанию парковки в ответе админки.
 *
 * @property ParkingDefaultTariffDto $resource
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(
    schema: 'ParkingDefaultTariffResponse',
    title: 'ParkingDefaultTariff',
    description: 'Тариф по умолчанию парковки',
    properties: [
        new OA\Property(property: 'tariff', ref: '#/components/schemas/TariffResponse'),
        new OA\Property(
            property: 'last_event_status',
            ref: '#/components/schemas/OutboxTariffEventStatusEnum',
            description: 'Статус доставки последнего события о тарифе; null — событий не было',
            nullable: true
        ),
    ],
    type: 'object'
)]
class ParkingDefaultTariffResponse extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tariff = new TariffResponse($this->resource->getTariff());

        return [
            'tariff' => $tariff->toArray($request),
            'last_event_status' => $this->resource->getLastEventStatus()?->value,
        ];
    }
}
