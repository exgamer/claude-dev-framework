<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Кеш тарифов по ID. Хранит тариф без скоупа: принадлежность парковке сверяет вызывающий после чтения.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffCacheRepositoryInterface
{
    /**
     * Промах — null, не ошибка.
     *
     * @throws OperationFailedAppException кеш недоступен
     */
    public function getById(int $id): ?Tariff;

    /**
     * @throws OperationFailedAppException кеш недоступен
     */
    public function set(Tariff $tariff): void;

    /**
     * @throws OperationFailedAppException кеш недоступен
     */
    public function forget(int $id): void;
}
