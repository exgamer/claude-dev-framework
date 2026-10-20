<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffSortEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
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
     * Частичный уникальный индекс «у парковки не больше одного тарифа по умолчанию» (миграция 2026_10_20_100000).
     */
    private const DEFAULT_TARIFF_UNIQUE_INDEX = 'tariffs_parking_id_default_unique';

    /**
     * SQLSTATE нарушения уникальности Postgres.
     */
    private const UNIQUE_VIOLATION_CODE = '23505';

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(Tariff $model)
    {
        $this->model = $model;

        parent::__construct();
    }

    public function getByIdInParkings(int $id, ?array $parkingIds): ?Tariff
    {
        $query = $this->getQuery()->whereKey($id);

        if ($parkingIds !== null) {
            $query->whereIn('parking_id', $parkingIds);
        }

        /** @var ?Tariff */
        return $query->first();
    }

    public function paginateTariffs(TariffFilterDto $filter, Page $page): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if ($filter->getParkingIds() !== null) {
            $query->whereIn('parking_id', $filter->getParkingIds());
        }

        if ($filter->getParkingId() !== null) {
            $query->where('parking_id', $filter->getParkingId());
        }

        if ($filter->getStatus() !== null) {
            $query->where('status', $filter->getStatus()->value);
        }

        match ($filter->getSort()) {
            TariffSortEnum::NAME_ASC => $query->orderBy('name'),
            TariffSortEnum::NAME_DESC => $query->orderByDesc('name'),
            TariffSortEnum::CREATED_AT_ASC => $query->orderBy('created_at'),
            TariffSortEnum::CREATED_AT_DESC => $query->orderByDesc('created_at'),
        };

        // Равные name/created_at без id дают плавающий порядок между страницами
        $query->orderByDesc('id');

        return $query
            ->select([
                'id',
                'parking_id',
                'name',
                'currency',
                'price',
                'is_default',
                'status',
                'grace_minutes',
                'scheduled_at',
                'created_at',
                'updated_at',
            ])
            ->paginate(perPage: $page->getPerPage(), page: $page->getNumber());
    }

    public function getDefaultByParkingId(int $parkingId): ?Tariff
    {
        /** @var ?Tariff */
        return $this->getQuery()
            ->where('parking_id', $parkingId)
            ->where('is_default', true)
            ->first();
    }

    public function allActiveByParkingId(int $parkingId): Collection
    {
        /** @var Collection<int, Tariff> */
        return $this->getQuery()
            ->where('parking_id', $parkingId)
            ->where('status', TariffStatusEnum::ACTIVE->value)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->select([
                'id',
                'parking_id',
                'name',
                'currency',
                'price',
                'is_default',
                'status',
                'grace_minutes',
                'scheduled_at',
            ])
            ->get();
    }

    public function allNotArchivedIdsByParkingId(int $parkingId): array
    {
        $statuses = array_map(
            static fn (TariffStatusEnum $status): string => $status->value,
            TariffStatusEnum::notArchived(),
        );

        /** @var list<int> */
        return $this->getQuery()
            ->where('parking_id', $parkingId)
            ->whereIn('status', $statuses)
            ->pluck('id')
            ->all();
    }

    public function createFromDto(TariffDto $dto, TariffStatusEnum $status, bool $isDefault): Tariff
    {
        /** @var Tariff $tariff */
        $tariff = $this->model->newInstance();
        // Дата активации пишется только при создании: перенос даты отложенный Job не догонит (граница эталона)
        $tariff->fill([
            ...$this->columns($dto),
            'status' => $status,
            'is_default' => $isDefault,
            'scheduled_at' => $dto->getScheduledAt(),
        ])->save();

        return $tariff;
    }

    public function updateFromDto(Tariff $tariff, TariffDto $dto): void
    {
        $tariff->fill($this->columns($dto))->save();
    }

    public function setDefault(int $id, bool $isDefault): bool
    {
        try {
            $this->getQuery()
                ->whereKey($id)
                ->update(['is_default' => $isDefault]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e, self::DEFAULT_TARIFF_UNIQUE_INDEX)) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function activateScheduled(int $id): bool
    {
        // Условие по статусу — в самом UPDATE: параллельный Job или отключение парковки не дают активировать дважды
        $updated = $this->getQuery()
            ->whereKey($id)
            ->where('status', TariffStatusEnum::SCHEDULED->value)
            ->update([
                'status' => TariffStatusEnum::ACTIVE->value,
                'is_default' => true,
                'scheduled_at' => null,
            ]);

        return $updated > 0;
    }

    public function archive(int $id): void
    {
        $this->getQuery()
            ->whereKey($id)
            ->update([
                'status' => TariffStatusEnum::ARCHIVED->value,
                'is_default' => false,
            ]);
    }

    public function archiveNotArchivedByParkingId(int $parkingId): int
    {
        $statuses = array_map(
            static fn (TariffStatusEnum $status): string => $status->value,
            TariffStatusEnum::notArchived(),
        );

        return $this->getQuery()
            ->where('parking_id', $parkingId)
            ->whereIn('status', $statuses)
            ->update(['status' => TariffStatusEnum::ARCHIVED->value]);
    }

    /**
     * Нарушение именно индекса $index: SQLSTATE 23505 и имя индекса в тексте ошибки драйвера; чужой индекс — не оно.
     */
    private function isUniqueViolation(QueryException $e, string $index): bool
    {
        return (string) $e->getCode() === self::UNIQUE_VIOLATION_CODE
            && str_contains($e->getMessage(), "\"{$index}\"");
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
            'price' => $dto->getPrice(),
            'grace_minutes' => $dto->getGraceMinutes(),
        ];
    }
}
