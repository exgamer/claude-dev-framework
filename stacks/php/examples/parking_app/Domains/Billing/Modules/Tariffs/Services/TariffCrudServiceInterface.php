<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;

/**
 * Интерфейс CRUD-сервиса тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffCrudServiceInterface
{
    public function findById(int $id): ?Tariff;

    public function search(SearchDataObject $dto): array;

    public function create(TariffDto $dto): Tariff;

    /**
     * Полная замена (PUT): в DTO — все поля тарифа.
     */
    public function update(int $id, TariffDto $dto): Tariff;
}
