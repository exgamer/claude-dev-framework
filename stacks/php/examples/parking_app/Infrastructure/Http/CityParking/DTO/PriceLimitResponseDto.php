<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\CityParking\DTO;

use MPS\Core\DataObjects\DataObject;

/**
 * Ответ реестра парковок на запрос лимита цены (GET /parkings/{id}/price-limit): контракт внешней системы, не доменная модель.
 *
 * @author Имя Фамилия <name@example.com>
 */
class PriceLimitResponseDto extends DataObject
{
    /** Предельная цена часа в минимальных единицах валюты запроса */
    private int $maxPrice;

    public function getMaxPrice(): int
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(int $value): self
    {
        $this->maxPrice = $value;

        return $this;
    }
}
