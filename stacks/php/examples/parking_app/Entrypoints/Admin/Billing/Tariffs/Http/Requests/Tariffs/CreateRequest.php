<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use MPS\Core\Enums\CurrencyEnum;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TariffCreateRequest',
    required: ['parking_id', 'name'],
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', description: 'Валюта', type: 'string', example: 'KZT', nullable: true),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
    ],
    type: 'object'
)]
class CreateRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parking_id' => ['required', 'integer', 'gt:0'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', Rule::enum(CurrencyEnum::class)],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }
}
