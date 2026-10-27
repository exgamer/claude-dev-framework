<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Ключи атрибутов запроса, которые выставляет middleware подпроекта.
 *
 * @author Имя Фамилия <name@example.com>
 */
enum RequestAttributeEnum: string implements Enumerable
{
    use EnumerableTrait;

    /** Парковки, доступные администратору; null — без ограничения (super-admin) */
    case PARKING_IDS = 'parking_ids';
}
