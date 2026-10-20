<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs\DTO;

use MPS\Core\DataObjects\DataObject;

/**
 * Результат сценария двух доменов: данные парковки (Catalog) вместе с её действующими тарифами (Billing).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ParkingTariffsDto extends DataObject
{
    private int $parkingId;

    private string $parkingName;

    private ParkingTariffItemsDto $tariffs;

    public function getParkingId(): int
    {
        return $this->parkingId;
    }

    public function setParkingId(int $value): self
    {
        $this->parkingId = $value;

        return $this;
    }

    public function getParkingName(): string
    {
        return $this->parkingName;
    }

    public function setParkingName(string $value): self
    {
        $this->parkingName = $value;

        return $this;
    }

    public function getTariffs(): ParkingTariffItemsDto
    {
        return $this->tariffs;
    }

    public function setTariffs(ParkingTariffItemsDto $value): self
    {
        $this->tariffs = $value;

        return $this;
    }
}
