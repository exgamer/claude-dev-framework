<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use MPS\Core\Enums\CurrencyEnum;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TariffUpdateRequest',
    description: 'PUT — полная замена: все поля обязательны к передаче, null очищает необязательное поле',
    required: ['parking_id', 'name', 'currency', 'grace_minutes'],
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', description: 'Валюта', type: 'string', example: 'KZT', nullable: true),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
    ],
    type: 'object'
)]
class UpdateRequest extends Request
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
            // PUT — полные данные: ключ обязателен, null — очистить (stacks/php/conventions.md, п. 12)
            'currency' => ['present', 'nullable', 'string', Rule::enum(CurrencyEnum::class)],
            'grace_minutes' => ['present', 'nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }
}
