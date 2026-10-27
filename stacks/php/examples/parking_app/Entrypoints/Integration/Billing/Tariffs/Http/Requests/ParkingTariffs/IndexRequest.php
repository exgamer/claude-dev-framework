<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Requests\ParkingTariffs;

use App\ParkingApp\Core\Requests\Request;

/**
 * Тарифы парковки для партнёра: id парковки в пути — положительное целое (0 — 422).
 *
 * @author Имя Фамилия <name@example.com>
 */
class IndexRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
        ];
    }
}
