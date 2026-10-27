<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Jobs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivatePlannedTariffCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MPS\Core\Exceptions\OperationFailedAppException;
use Throwable;

/**
 * Отложенная активация запланированного тарифа; ставит CreateTariffWorkflow после коммита с задержкой до scheduled_at.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ActivatePlannedTariffJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $tariffId,
    ) {
        $this->onConnection('database');
    }

    public function getTariffId(): int
    {
        return $this->tariffId;
    }

    /**
     * Только вызов Command домена: проверка статуса и запись — в нём, повтор безопасен.
     *
     * @throws OperationFailedAppException
     * @throws Throwable
     */
    public function handle(ActivatePlannedTariffCommand $command): void
    {
        $command->execute($this->tariffId);
    }
}
