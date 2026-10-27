<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\Request;

/**
 * Сделать тариф тарифом по умолчанию: id пути — положительное целое (0 — 422).
 *
 * @author Имя Фамилия <name@example.com>
 */
class SetDefaultRequest extends Request
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
