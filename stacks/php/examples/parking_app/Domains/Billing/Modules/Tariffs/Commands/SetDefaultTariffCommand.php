<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use Throwable;

/**
 * Назначение тарифа по умолчанию: у парковки он один, флаг прежнего снимается.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class SetDefaultTariffCommand
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly TariffCacheRepositoryInterface $cacheRepository,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    /**
     * Тариф ищется в пределах парковок; null в $parkingIds — без ограничения. Чужой — 404.
     * Поиск прежнего и снятие его флага — для обычного случая; от параллельной смены защищает уникальный индекс:
     * отказ записи → 422, транзакция откатывается. Кеш обоих тарифов сбрасывается после коммита.
     *
     * @param ?list<int> $parkingIds
     *
     * @throws NotFoundAppException
     * @throws ValidationAppException
     * @throws Throwable
     */
    public function execute(int $id, ?array $parkingIds): Tariff
    {
        $tariff = $this->repository->getByIdInParkings($id, $parkingIds);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        if ($tariff->is_default) {
            return $tariff;
        }

        /** @var ?int $previousId */
        $previousId = $this->transactionManager->run(function () use ($tariff): ?int {
            $previous = $this->repository->getDefaultByParkingId($tariff->parking_id);

            if ($previous) {
                $this->repository->setDefault($previous->id, false);
            }

            if (! $this->repository->setDefault($tariff->id, true)) {
                throw new ValidationAppException('VALIDATION ERROR', [
                    'is_default' => ['Тариф по умолчанию этой парковки только что изменён другим запросом, обновите данные и повторите'],
                ]);
            }

            return $previous?->id;
        });

        if ($previousId !== null) {
            $this->forgetCached($previousId);
        }

        $this->forgetCached($tariff->id);

        /** @var Tariff */
        return $this->repository->getByIdInParkings($id, $parkingIds);
    }

    /**
     * Запись уже зафиксирована: сбой сброса кеша её не отменяет — ошибка уходит в Sentry.
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
