<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
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

    public function createFromDto(TariffDto $dto, TariffStatusEnum $status, bool $isDefault): Tariff
    {
        /** @var Tariff $tariff */
        $tariff = $this->model->newInstance();
        $tariff->fill([
            ...$this->columns($dto),
            'status' => $status,
            'is_default' => $isDefault,
        ])->save();

        return $tariff;
    }

    public function updateFromDto(Tariff $tariff, TariffDto $dto): void
    {
        $tariff->fill($this->columns($dto))->save();
    }

    public function setDefault(int $id, bool $isDefault): void
    {
        $this->getQuery()
            ->whereKey($id)
            ->update(['is_default' => $isDefault]);
    }

    /**
     * Колонки тарифа из DTO: status и is_default задаются отдельно.
     *
     * @return array<string, mixed>
     */
    private function columns(TariffDto $dto): array
    {
        return [
            'parking_id' => $dto->getParkingId(),
            'name' => $dto->getName(),
            'currency' => $dto->getCurrency(),
            'grace_minutes' => $dto->getGraceMinutes(),
        ];
    }
}
