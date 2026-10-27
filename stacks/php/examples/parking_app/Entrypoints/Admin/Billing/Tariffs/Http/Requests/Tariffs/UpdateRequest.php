<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Common\Enums\TimeUnitEnum;
use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use MPS\Core\Enums\CurrencyEnum;
use OpenApi\Attributes as OA;

/**
 * Формат тела PUT: каждое поле обязательно к передаче (неприсланное — 422), null очищает grace_minutes.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(
    schema: 'TariffUpdateRequest',
    description: 'PUT — полная замена: все поля обязательны к передаче, null очищает необязательное поле',
    required: ['parking_id', 'name', 'currency', 'price', 'grace_minutes'],
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', ref: '#/components/schemas/CurrencyEnum'),
        new OA\Property(property: 'price', description: 'Цена часа в минимальных единицах валюты', type: 'integer', example: 50000),
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
            'id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
            'parking_id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'grace_minutes' => ['present', 'nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],
        ];
    }
}
