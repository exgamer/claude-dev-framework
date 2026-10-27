<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\CityParking\PriceLimits\Mocks;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use MPS\Core\Enums\CurrencyEnum;

/**
 * Заглушка реестра для тестового окружения: лимит не задан.
 *
 * @author Имя Фамилия <name@example.com>
 */
class CityParkingPriceLimitsRepositoryMock implements PriceLimitRepositoryInterface
{
    public function getMaxPrice(int $parkingId, CurrencyEnum $currency): ?int
    {
        return null;
    }
}
