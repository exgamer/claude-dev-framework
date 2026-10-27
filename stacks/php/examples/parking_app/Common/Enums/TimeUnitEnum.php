<?php

declare(strict_types=1);

namespace App\ParkingApp\Common\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Единица времени: льготный период, длительность стоянки, расчётный интервал тарифа.
 *
 * @author Имя Фамилия <name@example.com>
 */
enum TimeUnitEnum: string implements Enumerable
{
    use EnumerableTrait;

    case MINUTE = 'minute';
    case HOUR = 'hour';
    case DAY = 'day';

    public function minutes(): int
    {
        return match ($this) {
            self::MINUTE => 1,
            self::HOUR => 60,
            self::DAY => 1440,
        };
    }
}
