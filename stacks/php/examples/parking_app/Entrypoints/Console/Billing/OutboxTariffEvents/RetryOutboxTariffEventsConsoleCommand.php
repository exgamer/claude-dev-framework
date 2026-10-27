<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Console\Billing\OutboxTariffEvents;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\RetryOutboxTariffEventsCommand;
use Illuminate\Console\Command;
use Throwable;

/**
 * Повтор отправки зависших событий о тарифах по расписанию (routes/console.php хоста).
 *
 * @author Имя Фамилия <name@example.com>
 */
class RetryOutboxTariffEventsConsoleCommand extends Command
{
    protected $signature = 'billing:outbox-tariff-events:retry';

    protected $description = 'Повторяет отправку в RabbitMQ зависших событий о тарифах, пока они есть';

    /**
     * Параллельный запуск безопасен: выборка пропускает строки, взятые другим прогоном.
     * Сбой отдельного события Command отправил в трекер; ошибка выборки — наверх, cron падает и уходит в Sentry.
     *
     * @throws Throwable
     */
    public function handle(RetryOutboxTariffEventsCommand $command): int
    {
        $result = $command->execute();

        $this->info("События о тарифах: опубликовано {$result->getPublished()}, не отправлено {$result->getFailed()}");

        return self::SUCCESS;
    }
}
