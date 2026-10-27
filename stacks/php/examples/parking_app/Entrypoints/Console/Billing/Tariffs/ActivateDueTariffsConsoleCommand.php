<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Console\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivateDueTariffsCommand;
use Illuminate\Console\Command;

/**
 * Активация запланированных тарифов с наступившей датой по расписанию (routes/console.php хоста).
 *
 * @author Имя Фамилия <name@example.com>
 */
class ActivateDueTariffsConsoleCommand extends Command
{
    protected $signature = 'billing:tariffs:activate-due';

    protected $description = 'Активирует запланированные тарифы, дата активации которых наступила';

    public function handle(ActivateDueTariffsCommand $command): int
    {
        $processed = $command->execute();

        $this->info("Запланированные тарифы с наступившей датой: обработано {$processed}");

        return self::SUCCESS;
    }
}
