<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http;

use App\ParkingApp\Core\Enums\RequestAttributeEnum;
use Illuminate\Http\Request;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Чтение контекста, который middleware подпроекта положила в атрибуты запроса.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class RequestHelper
{
    /**
     * Парковки, доступные администратору; null — без ограничения.
     * Нет атрибута (маршрут без resolve.admin.parkings) — исключение, а не «без ограничения».
     *
     * @return ?list<int>
     *
     * @throws InvalidConfigurationAppException
     */
    public static function getParkingIds(Request $request): ?array
    {
        if (! $request->attributes->has(RequestAttributeEnum::PARKING_IDS->value)) {
            throw new InvalidConfigurationAppException('На маршруте нет middleware resolve.admin.parkings');
        }

        /** @var ?list<int> */
        return $request->attributes->get(RequestAttributeEnum::PARKING_IDS->value);
    }
}
