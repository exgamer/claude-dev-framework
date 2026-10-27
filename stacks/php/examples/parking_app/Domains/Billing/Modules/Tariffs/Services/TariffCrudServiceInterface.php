<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;

/**
 * Чтение и изменение тарифов с проверкой скоупа парковок и доменных инвариантов.
 *
 * @author Имя Фамилия <name@example.com>
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
     * @throws ServerConfigurationException
     */
    public function paginate(TariffFilterDto $filter, Page $page): LengthAwarePaginator;

    /**
     * Доменные инварианты DTO (TariffDtoValidator); нарушены — 422 по полям. Сценарий зовёт до внешних вызовов и транзакции.
     *
     * @throws ValidationAppException
     */
    public function assertValid(TariffDto $dto): void;

    /**
     * С датой активации тариф создаётся запланированным (SCHEDULED), без неё — действующим (ACTIVE); не по умолчанию —
     * начальное состояние задаёт Tariff::initState(). Возвращает перечитанную запись: колонки с DEFAULT БД заполнены.
     *
     * @throws ValidationAppException
     * @throws OperationFailedAppException созданный тариф не перечитан
     */
    public function create(TariffDto $dto): Tariff;

    /**
     * Полная замена: DTO пишется целиком, null в необязательном поле очищает его; status и is_default не меняются.
     * Тариф чужой парковки — 404.
     *
     * @param ?list<int> $parkingIds
     *
     * @throws NotFoundAppException
     * @throws ValidationAppException
     */
    public function update(int $id, ?array $parkingIds, TariffDto $dto): Tariff;
}
