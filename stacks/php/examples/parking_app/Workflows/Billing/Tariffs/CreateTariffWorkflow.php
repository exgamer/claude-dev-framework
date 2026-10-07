<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Exceptions\ValidationAppException;

/**
 * Создаёт тариф с проверкой парковки.
 *
 * Billing-сервис не знает о Catalog-домене — эта связь живёт здесь.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class CreateTariffWorkflow
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly TariffCrudServiceInterface $tariffCrudService,
    ) {
    }

    /**
     * @throws ValidationAppException
     */
    public function execute(TariffDto $dto): Tariff
    {
        if (! $this->parkingRepository->oneById($dto->getParkingId())) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'parking_id' => ['Парковка не найдена'],
            ]);
        }

        return $this->tariffCrudService->create($dto);
    }
}
