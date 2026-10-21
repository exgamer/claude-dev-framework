<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Переход Archive для всех тарифов парковки: парковку отключили — действующие и запланированные тарифы в архив.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class DeactivateParkingTariffsCommand
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly TariffCacheRepositoryInterface $cacheRepository,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    /**
     * Массовый переход — одним условным UPDATE: допустимость Tariff::canArchive() (не в архиве) — условием запроса,
     * тарифы по одному не читаются. Возвращает число изменённых; повтор безопасен — архивные запрос не выбирает.
     * Кеш изменённых сбрасывается после записи.
     */
    public function execute(int $parkingId): int
    {
        $ids = $this->repository->allNotArchivedIdsByParkingId($parkingId);
        $archived = $this->repository->archiveNotArchivedByParkingId($parkingId);

        foreach ($ids as $id) {
            $this->forgetCached($id);
        }

        return $archived;
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
