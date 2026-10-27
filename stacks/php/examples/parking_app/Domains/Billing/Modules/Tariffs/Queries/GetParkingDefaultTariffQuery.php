<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Queries;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\ParkingDefaultTariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffEventStatusRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;

/**
 * Тариф по умолчанию парковки вместе со статусом доставки последнего события о нём.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class GetParkingDefaultTariffQuery
{
    public function __construct(
        private readonly TariffRepositoryInterface $tariffRepository,
        private readonly TariffEventStatusRepositoryInterface $eventStatusRepository,
    ) {
    }

    /**
     * Только чтение — без транзакции. Скоуп парковки проверяет точка входа до вызова.
     * Тарифа по умолчанию нет — null, события не запрашиваются.
     */
    public function execute(int $parkingId): ?ParkingDefaultTariffDto
    {
        $tariff = $this->tariffRepository->getDefaultByParkingId($parkingId);

        if (! $tariff) {
            return null;
        }

        return ParkingDefaultTariffDto::make()
            ->setTariff($tariff)
            ->setLastEventStatus($this->eventStatusRepository->getLatestStatusByTariffId($tariff->id));
    }
}
