<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs\DTO;

use MPS\Core\DataObjects\DataObject;
use MPS\Core\Enums\CurrencyEnum;

/**
 * Строка списка для партнёра: цена в минимальных единицах, льготные минуты, признак «по умолчанию»; без статуса и дат.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ParkingTariffDto extends DataObject
{
    private int $id;

    private string $name;

    private CurrencyEnum $currency = CurrencyEnum::KZT;

    /** Цена часа в минимальных единицах валюты (тиын для KZT) */
    private int $price;

    private ?int $graceMinutes = null;

    private bool $isDefault = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;

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

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $value): self
    {
        $this->isDefault = $value;

        return $this;
    }
}
