<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\Request;
use OpenApi\Attributes as OA;

/**
 * Query тарифа по умолчанию: parking_id обязателен — у парковки один тариф по умолчанию.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Parameter(
    parameter: 'TariffsViewDefaultRequestParkingId',
    name: 'parking_id',
    description: 'ID парковки',
    in: 'query',
    required: true,
    schema: new OA\Schema(type: 'integer', minimum: 1)
)]
class ViewDefaultRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parking_id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
        ];
    }
}
