<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;

/**
 * Интерфейс репозитория тарифов.
 *
 * Возвращает Eloquent-модель из Infrastructure — разрешено для PHP-стека (решение P-4).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffRepositoryInterface extends CRUDRepositoryInterface
{
    public function getDefaultByParkingId(int $parkingId): ?Tariff;
}
