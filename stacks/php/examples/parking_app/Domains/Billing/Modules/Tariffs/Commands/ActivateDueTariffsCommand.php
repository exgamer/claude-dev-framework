<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Throwable;

/**
 * Страховка отложенной активации: запланированные тарифы, дата которых наступила, а Job их не активировал.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class ActivateDueTariffsCommand
{
    /**
     * Тарифов за прогон: тариф с постоянной ошибкой остаётся в выборке, поэтому прогон — одна пачка, без цикла.
     */
    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly TariffRepositoryInterface $repository,
        private readonly ActivatePlannedTariffCommand $activateCommand,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    /**
     * Активация — тем же ActivatePlannedTariffCommand, что у Job: тариф, активированный Job параллельно, пропускается.
     * Ошибка одного тарифа — report() и дальше; ошибка выборки — наверх. Возвращает число тарифов без ошибки.
     */
    public function execute(): int
    {
        $ids = $this->repository->allDueScheduledIds(self::BATCH_SIZE);
        $processed = 0;

        foreach ($ids as $id) {
            try {
                $this->activateCommand->execute($id);
                $processed++;
            } catch (Throwable $e) {
                $this->exceptionHandler->report($e);
            }
        }

        return $processed;
    }
}
