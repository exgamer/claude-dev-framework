<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\PartnerBilling\DTO;

use MPS\Core\DataObjects\DataObject;
use MPS\Core\Enums\CurrencyEnum;

/**
 * Запрос партнёрскому биллингу о новом тарифе (POST /tariffs): контракт внешней системы, не доменная модель.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PartnerTariffRequestDto extends DataObject
{
    /** ID тарифа у нас — для партнёра внешний */
    private int $externalId;

    private int $parkingId;

    private string $name;

    private CurrencyEnum $currency;

    /** Цена часа в минимальных единицах валюты */
    private int $price;

    public function getExternalId(): int
    {
        return $this->externalId;
    }

    public function setExternalId(int $value): self
    {
        $this->externalId = $value;

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
}
