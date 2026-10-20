<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use MPS\Core\DataObjects\DataObject;

/**
 * Событие справочника handbook.parking.deactivated: парковку отключили.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ParkingDeactivatedEventDto extends DataObject
{
    /** Nullable: payload внешний, обязательность проверяет обработчик */
    private ?int $parkingId = null;

    public function getParkingId(): ?int
    {
        return $this->parkingId;
    }

    public function setParkingId(?int $value): self
    {
        $this->parkingId = $value;

        return $this;
    }
}
