<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use Carbon\Carbon;
use MPS\Core\DataObjects\DataObject;
use MPS\Core\Enums\CurrencyEnum;

/**
 * Данные тарифа на запись — от запроса до репозитория (создание и полная замена); цена — в минимальных единицах валюты.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffDto extends DataObject
{
    private int $parkingId;

    private string $name;

    private CurrencyEnum $currency = CurrencyEnum::KZT;

    /** Цена часа в минимальных единицах валюты (тиын для KZT) */
    private int $price;

    private ?int $graceMinutes = null;

    /** Дата активации: задана — тариф создаётся запланированным, null — сразу действующим */
    private ?Carbon $scheduledAt = null;

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

    public function getScheduledAt(): ?Carbon
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?Carbon $value): self
    {
        $this->scheduledAt = $value;

        return $this;
    }
}
