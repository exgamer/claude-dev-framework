<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Queries;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\ParkingDefaultTariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;

/**
 * Тариф по умолчанию парковки вместе со статусом доставки последнего события о нём (модуль OutboxTariffEvents).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class GetParkingDefaultTariffQuery
{
    public function __construct(
        private readonly TariffRepositoryInterface $tariffRepository,
        private readonly OutboxTariffEventRepositoryInterface $outboxRepository,
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

        $lastEvent = $this->outboxRepository->getLatestByTariffId($tariff->id);

        return ParkingDefaultTariffDto::make()
            ->setTariff($tariff)
            ->setLastEventStatus($lastEvent?->status);
    }
}
