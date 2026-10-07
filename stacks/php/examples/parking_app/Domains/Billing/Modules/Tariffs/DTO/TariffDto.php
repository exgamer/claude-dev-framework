<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use MPS\Core\DataObjects\DataObject;
use MPS\Core\Enums\CurrencyEnum;

/**
 * DTO тарифа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffDto extends DataObject
{
    private int $parkingId;

    private string $name;

    private CurrencyEnum $currency = CurrencyEnum::KZT;

    private ?int $graceMinutes = null;

    public function getParkingId(): int
    {
        return $this->parkingId;
    }

    public function setParkingId(int $value): self
    {
        $this->parkingId = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    public function getCurrency(): CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $value): self
    {
        $this->currency = $value;

        return $this;
    }

    public function getGraceMinutes(): ?int
    {
        return $this->graceMinutes;
    }

    public function setGraceMinutes(?int $value): self
    {
        $this->graceMinutes = $value;

        return $this;
    }
}
