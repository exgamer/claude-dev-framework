<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Common\Enums\TimeUnitEnum;
use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use MPS\Core\Enums\CurrencyEnum;
use OpenApi\Attributes as OA;

/**
 * Формат тела создания тарифа: без scheduled_at — действующий сразу, с ним — запланированный; currency не передана — KZT.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(
    schema: 'TariffCreateRequest',
    required: ['parking_id', 'name', 'price'],
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'currency', description: 'Валюта, по умолчанию KZT', ref: '#/components/schemas/CurrencyEnum'),
        new OA\Property(property: 'price', description: 'Цена часа в минимальных единицах валюты; не выше лимита реестра парковок', type: 'integer', example: 50000),
        new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'scheduled_at', description: 'Дата активации, Y-m-d H:i:s, не в прошлом: задана — тариф создаётся запланированным и в эту дату становится тарифом по умолчанию', type: 'string', pattern: '^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$', example: '2026-11-01 00:00:00', nullable: true),
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
            'parking_id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', Rule::enum(CurrencyEnum::class)],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
