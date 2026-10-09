<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Exceptions\NotFoundAppException;
use Throwable;

/**
 * Назначение тарифа по умолчанию. Вынесено из сервиса: снять флаг со старого и поставить новому — в одной транзакции.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class SetDefaultTariffCommand
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    /**
     * @throws NotFoundAppException
     * @throws Throwable
     */
    public function execute(int $id): Tariff
    {
        /** @var ?Tariff $tariff */
        $tariff = $this->repository->oneById($id);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        if ($tariff->is_default) {
            return $tariff;
        }

        $this->transactionManager->run(function () use ($tariff) {
            $previous = $this->repository->getDefaultByParkingId($tariff->parking_id);

            if ($previous) {
                $this->repository->setDefault($previous->id, false);
            }

            $this->repository->setDefault($tariff->id, true);
        });

        /** @var Tariff */
        return $this->repository->oneById($id);
    }
}
