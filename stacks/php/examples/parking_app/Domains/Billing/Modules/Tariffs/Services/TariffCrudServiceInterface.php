<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\ValidationAppException;

/**
 * Интерфейс CRUD-сервиса тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffCrudServiceInterface
{
    /**
     * Тариф в пределах парковок; null в $parkingIds — без ограничения. Чужой — null (контроллер отвечает 404).
     * Сначала кеш (парковка сверяется после чтения), промах — БД с записью в кеш; кеш недоступен — БД.
     *
     * @param ?list<int> $parkingIds
     */
    public function findById(int $id, ?array $parkingIds): ?Tariff;

    /**
     * Скоуп парковок — полем фильтра (TariffFilterDto::setParkingIds, выставляет контроллер); не выставлен — исключение.
     *
     * @throws InvalidConfigurationAppException
     */
    public function paginate(TariffFilterDto $filter, Page $page): LengthAwarePaginator;

    /**
     * С датой активации тариф создаётся запланированным (SCHEDULED), без неё — действующим (ACTIVE); не по умолчанию.
     *
     * @throws ValidationAppException
     */
    public function create(TariffDto $dto): Tariff;

    /**
     * Полная замена: DTO пишется целиком, null в необязательном поле очищает его. Тариф чужой парковки — 404.
     *
     * @param ?list<int> $parkingIds
     *
     * @throws NotFoundAppException
     * @throws ValidationAppException
     */
    public function update(int $id, ?array $parkingIds, TariffDto $dto): Tariff;

    /**
     * Парковку отключили: действующие и запланированные тарифы — в архив, их кеш сбрасывается. Возвращает число
     * изменённых; повтор безопасен — архивные запрос не выбирает.
     */
    public function deactivateByParkingId(int $parkingId): int;
}
