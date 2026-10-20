<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Интерфейс репозитория тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffRepositoryInterface extends CRUDRepositoryInterface
{
    /**
     * Тариф по ID в пределах парковок; null в $parkingIds — без ограничения, передаётся явно. Чужой — null.
     *
     * @param ?list<int> $parkingIds
     */
    public function getByIdInParkings(int $id, ?array $parkingIds): ?Tariff;

    /**
     * Скоуп парковок — полем фильтра (setParkingIds); не выставлен — исключение, а не «все парковки».
     *
     * @throws InvalidConfigurationAppException
     */
    public function paginateTariffs(TariffFilterDto $filter, Page $page): LengthAwarePaginator;

    public function getDefaultByParkingId(int $parkingId): ?Tariff;

    /**
     * Действующие тарифы парковки: сначала тариф по умолчанию, затем по ID.
     *
     * @return Collection<int, Tariff>
     */
    public function allActiveByParkingId(int $parkingId): Collection;

    /**
     * ID действующих и запланированных тарифов парковки — те, что снимет archiveNotArchivedByParkingId.
     *
     * @return list<int>
     */
    public function allNotArchivedIdsByParkingId(int $parkingId): array;

    public function createFromDto(TariffDto $dto, TariffStatusEnum $status, bool $isDefault): Tariff;

    /**
     * Пишет колонки из DTO. false — тариф по умолчанию переносится на парковку, где тариф по умолчанию уже есть
     * (уникальный индекс): запись не сделана, вызывающий только бросает исключение.
     */
    public function updateFromDto(Tariff $tariff, TariffDto $dto): bool;

    /**
     * Пишет флаг. false — у парковки уже есть другой тариф по умолчанию (уникальный индекс, параллельная смена):
     * транзакция Postgres после этого прервана, вызывающий только бросает исключение.
     */
    public function setDefault(int $id, bool $isDefault): bool;

    /**
     * Запланированный тариф — действующим и по умолчанию, дата активации снимается. false — тариф уже не
     * запланирован (активирован или отключён параллельно): запись не изменена.
     */
    public function activateScheduled(int $id): bool;

    /**
     * Тариф — в архив, флаг «по умолчанию» снимается.
     */
    public function archive(int $id): void;

    /**
     * Действующие и запланированные тарифы парковки — в архив; возвращает число изменённых.
     */
    public function archiveNotArchivedByParkingId(int $parkingId): int;
}
