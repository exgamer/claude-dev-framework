<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Workflows\Billing\Tariffs\DTO\ParkingTariffItemsDto;
use App\ParkingApp\Workflows\Billing\Tariffs\DTO\ParkingTariffsDto;
use MPS\Core\Exceptions\NotFoundAppException;

/**
 * Действующие тарифы парковки (Billing) вместе с данными парковки (Catalog).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class ListParkingTariffsWorkflow
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly TariffRepositoryInterface $tariffRepository,
    ) {
    }

    /**
     * Только чтение — без транзакции.
     *
     * @throws NotFoundAppException парковки нет
     */
    public function execute(int $parkingId): ParkingTariffsDto
    {
        $parking = $this->parkingRepository->oneById($parkingId);

        if (! $parking) {
            throw new NotFoundAppException('Парковка не найдена');
        }

        $tariffs = $this->tariffRepository->allActiveByParkingId($parkingId);
        $items = ParkingTariffItemsDto::make()->setItems($tariffs->toArray());

        return ParkingTariffsDto::make()
            ->setParkingId($parkingId)
            ->setParkingName((string) $parking->name)
            ->setTariffs($items);
    }
}
