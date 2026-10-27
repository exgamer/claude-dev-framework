<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO;

use MPS\Core\DataObjects\DataObject;
use MPS\Core\Enums\CurrencyEnum;

/**
 * Данные тарифа в событии: снимок на момент события, тело сообщения.
 *
 * @author Имя Фамилия <name@example.com>
 */
class OutboxTariffEventDto extends DataObject
{
    private int $tariffId;

    private int $parkingId;

    private string $name;

    private CurrencyEnum $currency;

    /** Цена часа в минимальных единицах валюты */
    private int $price;

    private ?int $graceMinutes = null;

    public function getTariffId(): int
    {
        return $this->tariffId;
    }

    public function setTariffId(int $value): self
    {
        $this->tariffId = $value;

        return $this;
    }

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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $value): self
    {
        $this->price = $value;

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
