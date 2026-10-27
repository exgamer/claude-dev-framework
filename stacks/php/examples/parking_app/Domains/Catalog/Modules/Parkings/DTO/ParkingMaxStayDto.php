<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Catalog\Modules\Parkings\DTO;

use App\ParkingApp\Common\Enums\TimeUnitEnum;
use MPS\Core\DataObjects\DataObject;

/**
 * Наибольшая разрешённая длительность стоянки на парковке: количество единиц времени (Common — тот же словарь, что у Billing).
 *
 * @author Имя Фамилия <name@example.com>
 */
class ParkingMaxStayDto extends DataObject
{
    private int $amount;

    private TimeUnitEnum $unit;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $value): self
    {
        $this->amount = $value;

        return $this;
    }

    public function getUnit(): TimeUnitEnum
    {
        return $this->unit;
    }

    public function setUnit(TimeUnitEnum $value): self
    {
        $this->unit = $value;

        return $this;
    }
}
