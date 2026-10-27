<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Console\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\SetDefaultTariffCommand;
use Illuminate\Console\Command;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\ValidationAppException;
use Throwable;

/**
 * Назначение тарифа по умолчанию из консоли — для поддержки, когда админка недоступна.
 *
 * @author Имя Фамилия <name@example.com>
 */
class SetDefaultTariffConsoleCommand extends Command
{
    protected $signature = 'billing:tariffs:set-default
        {id : ID тарифа}
    ';

    protected $description = 'Назначает тариф тарифом по умолчанию его парковки, прежний теряет флаг';

    /**
     * Консоль запускает оператор с доступом к серверу: тариф ищется без ограничения по парковкам (null).
     *
     * @throws NotFoundAppException
     * @throws ValidationAppException
     * @throws Throwable
     */
    public function handle(SetDefaultTariffCommand $command): int
    {
        $id = (string) $this->argument('id');

        if (! ctype_digit($id) || (int) $id === 0) {
            $this->error('ID тарифа — целое положительное число');

            return self::INVALID;
        }

        $tariff = $command->execute((int) $id, null);

        $this->info("Тариф {$tariff->id} — по умолчанию у парковки {$tariff->parking_id}");

        return self::SUCCESS;
    }
}
