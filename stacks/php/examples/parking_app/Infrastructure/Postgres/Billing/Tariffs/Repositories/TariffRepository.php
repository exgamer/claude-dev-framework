<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Репозиторий тарифов
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffRepository extends CRUDRepository implements TariffRepositoryInterface
{
    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(Tariff $model)
    {
        $this->model = $model;

        parent::__construct();
    }

    protected function filterSearch(BuilderContract $query, array &$params = []): void
    {
        parent::filterSearch($query, $params);

        if (isset($params['parking_id'])) {
            $query->where('parking_id', $params['parking_id']);
        }

        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
    }

    public function getDefaultByParkingId(int $parkingId): ?Tariff
    {
        /** @var ?Tariff */
        return $this->getQuery()
            ->where('parking_id', $parkingId)
            ->where('is_default', true)
            ->where('status', TariffStatusEnum::ACTIVE->value)
            ->first();
    }
}
