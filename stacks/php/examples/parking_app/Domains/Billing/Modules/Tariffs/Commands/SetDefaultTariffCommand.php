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
 * Переход SetDefault: действующий тариф становится тарифом по умолчанию, флаг прежнего снимается — у парковки он один.
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
     * Тариф ищется в пределах парковок; null в $parkingIds — без ограничения. Чужой — 404, допустимость —
     * Tariff::canBeDefault() (нет — 422, в том числе у архивного с флагом), уже по умолчанию — без записи. Поиск прежнего и снятие его флага — для обычного
     * случая; от параллельной смены защищают условие UPDATE и уникальный индекс: отказ записи → 422, транзакция
     * откатывается. Кеш обоих тарифов сбрасывается после коммита.
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

        // Допустимость — раньше «уже по умолчанию»: массовый архив флаг не снимает, архивный с флагом — тоже 422
        if (! $tariff->canBeDefault()) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'id' => ['Тарифом по умолчанию назначается только действующий тариф: запланированный станет им в дату активации'],
            ]);
        }

        if ($tariff->is_default) {
            return $tariff;
        }

        /** @var ?int $previousId */
        $previousId = $this->transactionManager->run(function () use ($tariff): ?int {
            $previous = $this->repository->getDefaultByParkingId($tariff->parking_id);

            if ($previous) {
                $this->repository->unsetDefault($previous->id);
            }

            if (! $this->repository->setDefault($tariff->id)) {
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

        $updated = $this->repository->getByIdInParkings($id, $parkingIds);

        if (! $updated) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return $updated;
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
