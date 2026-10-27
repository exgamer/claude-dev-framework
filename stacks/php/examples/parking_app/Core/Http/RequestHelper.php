<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http;

use App\ParkingApp\Core\Enums\RequestAttributeEnum;
use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use Illuminate\Http\Request;

/**
 * Чтение контекста, который middleware подпроекта положила в атрибуты запроса.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class RequestHelper
{
    /**
     * Парковки, доступные администратору; null — без ограничения.
     * Нет атрибута (маршрут без resolve.admin.parkings) — исключение, а не «без ограничения».
     *
     * @return ?list<int>
     *
     * @throws ServerConfigurationException
     */
    public static function getParkingIds(Request $request): ?array
    {
        if (! $request->attributes->has(RequestAttributeEnum::PARKING_IDS->value)) {
            throw new ServerConfigurationException('На маршруте нет middleware resolve.admin.parkings');
        }

        /** @var ?list<int> */
        return $request->attributes->get(RequestAttributeEnum::PARKING_IDS->value);
    }
}
