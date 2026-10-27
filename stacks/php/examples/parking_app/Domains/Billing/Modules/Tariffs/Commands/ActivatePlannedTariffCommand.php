<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use MPS\Core\Exceptions\OperationFailedAppException;
use Throwable;

/**
 * Переход Activate: он становится действующим и тарифом по умолчанию, прежний по умолчанию — в архив.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class ActivatePlannedTariffCommand
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly TariffCacheRepositoryInterface $cacheRepository,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    /**
     * Повтор безопасен: тариф не найден или Tariff::canActivate() — нет (активирован, отключён вместе с парковкой) —
     * выход без записи. Вызывает отложенный Job — внутренняя точка входа, скоупа парковок нет (null). Кеш обоих тарифов
     * сбрасывается после коммита.
     *
     * @throws OperationFailedAppException тариф перестал быть запланированным (или прежний — действующим) между
     *                                     проверкой и записью — запись откатывается, повтор Job выйдет на проверке
     *                                     статуса
     * @throws Throwable
     */
    public function execute(int $tariffId): void
    {
        $tariff = $this->repository->getByIdInParkings($tariffId, null);

        if (! $tariff || ! $tariff->canActivate()) {
            return;
        }

        /** @var ?int $archivedId */
        $archivedId = $this->transactionManager->run(function () use ($tariff): ?int {
            $previous = $this->repository->getDefaultByParkingId($tariff->parking_id);
            $archivePrevious = $previous && $previous->id !== $tariff->id;

            // Сначала снять флаг прежнего: уникальный индекс не допускает двух тарифов по умолчанию у парковки.
            // Прежний уже в архиве (парковку отключали) — только снять флаг
            if ($archivePrevious && ! $previous->canArchive()) {
                $this->repository->unsetDefault($previous->id);
            } elseif ($archivePrevious && ! $this->repository->archive($previous->id)) {
                throw new OperationFailedAppException('Прежний тариф по умолчанию архивирован параллельно', [
                    'tariff_id' => $tariff->id,
                    'previous_tariff_id' => $previous->id,
                ]);
            }

            if (! $this->repository->activateScheduled($tariff->id)) {
                throw new OperationFailedAppException('Тариф уже не запланирован: активирован или отключён параллельно', [
                    'tariff_id' => $tariff->id,
                ]);
            }

            return $archivePrevious ? $previous->id : null;
        });

        if ($archivedId !== null) {
            $this->forgetCached($archivedId);
        }

        $this->forgetCached($tariff->id);
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
