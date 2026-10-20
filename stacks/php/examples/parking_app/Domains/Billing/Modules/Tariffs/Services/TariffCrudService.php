<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators\TariffDtoValidator;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use MPS\Core\Services\Service;

/**
 * Тариф по ID — кеш, затем БД со скоупом парковок; запись — после доменных инвариантов, со сбросом кеша; недоступный кеш запрос не роняет.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffCrudService extends Service implements TariffCrudServiceInterface
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly TariffCacheRepositoryInterface $cacheRepository,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    public function findById(int $id, ?array $parkingIds): ?Tariff
    {
        $cached = null;

        try {
            $cached = $this->cacheRepository->getById($id);
        } catch (OperationFailedAppException $e) {
            // Кеш недоступен — тариф читается из БД, запрос не падает
            $this->exceptionHandler->report($e);
        }

        if ($cached) {
            // Кеш хранит тариф без скоупа: чужой парковки — null, как из БД
            if ($parkingIds !== null && ! in_array($cached->parking_id, $parkingIds, true)) {
                return null;
            }

            return $cached;
        }

        $tariff = $this->repository->getByIdInParkings($id, $parkingIds);

        if (! $tariff) {
            return null;
        }

        try {
            $this->cacheRepository->set($tariff);
        } catch (OperationFailedAppException $e) {
            $this->exceptionHandler->report($e);
        }

        return $tariff;
    }

    /**
     * @throws InvalidConfigurationAppException
     */
    public function paginate(TariffFilterDto $filter, Page $page): LengthAwarePaginator
    {
        return $this->repository->paginateTariffs($filter, $page);
    }

    /**
     * @throws ValidationAppException
     */
    public function create(TariffDto $dto): Tariff
    {
        $errors = TariffDtoValidator::validate($dto);

        if ($errors) {
            throw new ValidationAppException('VALIDATION ERROR', $errors);
        }

        // С датой активации — запланированный: действующим и тарифом по умолчанию его сделает ActivatePlannedTariffJob
        $status = $dto->getScheduledAt() !== null ? TariffStatusEnum::SCHEDULED : TariffStatusEnum::ACTIVE;

        return $this->repository->createFromDto($dto, status: $status, isDefault: false);
    }

    public function deactivateByParkingId(int $parkingId): int
    {
        $ids = $this->repository->allNotArchivedIdsByParkingId($parkingId);
        $archived = $this->repository->archiveNotArchivedByParkingId($parkingId);

        foreach ($ids as $id) {
            $this->forgetCached($id);
        }

        return $archived;
    }

    /**
     * @throws NotFoundAppException
     * @throws ValidationAppException
     */
    public function update(int $id, ?array $parkingIds, TariffDto $dto): Tariff
    {
        $tariff = $this->repository->getByIdInParkings($id, $parkingIds);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        $errors = TariffDtoValidator::validate($dto);

        if ($errors) {
            throw new ValidationAppException('VALIDATION ERROR', $errors);
        }

        if (! $this->repository->updateFromDto($tariff->id, $dto)) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'parking_id' => ['У этой парковки уже есть тариф по умолчанию — сначала назначьте его другому тарифу'],
            ]);
        }

        $this->forgetCached($tariff->id);

        $updated = $this->repository->getByIdInParkings($id, $parkingIds);

        if (! $updated) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return $updated;
    }

    /**
     * Запись уже сохранена: сбой сброса кеша её не отменяет, устаревший тариф живёт до TTL — ошибка уходит в Sentry.
     */
    private function forgetCached(int $id): void
    {
        try {
            $this->cacheRepository->forget($id);
        } catch (OperationFailedAppException $e) {
            $this->exceptionHandler->report($e);
        }
    }
}
