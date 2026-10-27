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
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Тарифы в parking.tariffs: каждый публичный метод — один запрос, скоуп парковок — условием в запросе.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffRepository extends CRUDRepository implements TariffRepositoryInterface
{
    /**
     * Частичный уникальный индекс «у парковки не больше одного тарифа по умолчанию».
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
        $parkingIds = $filter->getParkingIds();

        if ($parkingIds !== null) {
            $query->whereIn('parking_id', $parkingIds);
        }

        if ($filter->getParkingId() !== null) {
            $query->where('parking_id', $filter->getParkingId());
        }

        if ($filter->getStatus() !== null) {
            $query->where('status', $filter->getStatus()->value);
        }

        [$column, $direction] = match ($filter->getSort()) {
            TariffSortEnum::NAME_ASC => ['name', 'asc'],
            TariffSortEnum::NAME_DESC => ['name', 'desc'],
            TariffSortEnum::CREATED_AT_ASC => ['created_at', 'asc'],
            TariffSortEnum::CREATED_AT_DESC => ['created_at', 'desc'],
        };

        // id — в том же направлении: равные name/created_at иначе плавают между страницами, а смешанный порядок
        // индекс (parking_id, колонка, id) целиком не обслуживает
        $query->orderBy($column, $direction)->orderBy('id', $direction);

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

    public function allDueScheduledIds(int $limit): array
    {
        /** @var list<int> */
        return $this->getQuery()
            ->where('status', TariffStatusEnum::SCHEDULED->value)
            ->where('scheduled_at', '<=', Carbon::now())
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();
    }

    public function createFromDto(TariffDto $dto): Tariff
    {
        /** @var Tariff $tariff */
        $tariff = $this->model->newInstance();
        $tariff->fill($this->columns($dto))
            ->initState($dto->getScheduledAt())
            ->save();

        return $tariff;
    }

    /**
     * @throws QueryException ошибка БД, кроме нарушения индекса тарифа по умолчанию (оно — false)
     */
    public function updateFromDto(int $id, TariffDto $dto): bool
    {
        $columns = $this->columns($dto);

        try {
            $this->getQuery()
                ->whereKey($id)
                ->update($columns);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e, self::DEFAULT_TARIFF_UNIQUE_INDEX)) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    /**
     * @throws QueryException ошибка БД, кроме нарушения индекса тарифа по умолчанию (оно — false)
     */
    public function setDefault(int $id): bool
    {
        // Условие canBeDefault() — в самом UPDATE: тариф, архивированный параллельно, флаг не получит
        try {
            $updated = $this->getQuery()
                ->whereKey($id)
                ->where('status', TariffStatusEnum::ACTIVE->value)
                ->where('is_default', false)
                ->update(['is_default' => true]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e, self::DEFAULT_TARIFF_UNIQUE_INDEX)) {
                return false;
            }

            throw $e;
        }

        return $updated > 0;
    }

    public function unsetDefault(int $id): void
    {
        $this->getQuery()
            ->whereKey($id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
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

    public function archive(int $id): bool
    {
        // Условие canArchive() — в самом UPDATE
        $updated = $this->getQuery()
            ->whereKey($id)
            ->whereIn('status', TariffStatusEnum::notArchivedValues())
            ->update([
                'status' => TariffStatusEnum::ARCHIVED->value,
                'is_default' => false,
            ]);

        return $updated > 0;
    }

    public function archiveNotArchivedByParkingId(int $parkingId): array
    {
        // RETURNING: id изменённых — тем же запросом; отдельное чтение до UPDATE пропустило бы тариф, созданный между ними
        [$active, $scheduled] = TariffStatusEnum::notArchivedValues();

        $rows = $this->model->getConnection()->select(
            "UPDATE {$this->model->getTable()} SET status = ?
            WHERE parking_id = ? AND status IN (?, ?) AND deleted_at IS NULL
            RETURNING id",
            [TariffStatusEnum::ARCHIVED->value, $parkingId, $active, $scheduled],
        );

        $ids = array_map(static fn (object $row): int => (int) $row->id, $rows);
        sort($ids);

        return $ids;
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
     * Колонки тарифа из DTO без состояния: status, is_default и scheduled_at задают initState() и методы переходов;
     * update() мимо $casts — enum значением.
     *
     * @return array<string, mixed>
     */
    private function columns(TariffDto $dto): array
    {
        return [
            'parking_id' => $dto->getParkingId(),
            'name' => $dto->getName(),
            'currency' => $dto->getCurrency()->value,
            'price' => $dto->getPrice(),
            'grace_minutes' => $dto->getGraceMinutes(),
        ];
    }
}
