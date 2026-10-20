<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventRetryResultDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use Throwable;

/**
 * Повтор зависших событий о тарифах (процесс упал после коммита, брокер был недоступен) пачками, пока выборка полная.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class RetryOutboxTariffEventsCommand
{
    private readonly int $batchSize;

    private readonly int $staleAfterSeconds;

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(
        private readonly OutboxTariffEventRepositoryInterface $repository,
        private readonly PublishOutboxTariffEventCommand $publishCommand,
        private readonly ExceptionHandler $exceptionHandler,
        Config $config,
    ) {
        $batchSize = $config->get('parking-app.outbox_tariff_events.retry_batch_size');

        if (! is_numeric($batchSize) || (int) $batchSize <= 0) {
            throw new InvalidConfigurationAppException('Не задан OUTBOX_TARIFF_EVENTS_RETRY_BATCH_SIZE');
        }

        // Сколько событие не менялось, чтобы считаться зависшим; на столько же взятое скрыто от параллельного прогона
        $staleAfterSeconds = $config->get('parking-app.outbox_tariff_events.retry_stale_after_seconds');

        if (! is_numeric($staleAfterSeconds) || (int) $staleAfterSeconds <= 0) {
            throw new InvalidConfigurationAppException('Не задан OUTBOX_TARIFF_EVENTS_RETRY_STALE_AFTER_SECONDS');
        }

        $this->batchSize = (int) $batchSize;
        $this->staleAfterSeconds = (int) $staleAfterSeconds;
    }

    /**
     * Сбой отдельного события — в трекер через report(), прогон продолжается; ошибка выборки — наверх, прогон стоп.
     *
     * @throws Throwable
     */
    public function execute(): OutboxTariffEventRetryResultDto
    {
        $published = 0;
        $failed = 0;

        do {
            $ids = $this->repository->claimForRetry($this->batchSize, $this->staleAfterSeconds);

            foreach ($ids as $id) {
                try {
                    $this->publishCommand->execute($id);
                    $published++;
                } catch (OperationFailedAppException $e) {
                    $failed++;
                    $this->exceptionHandler->report($e);
                }
            }
        } while (count($ids) === $this->batchSize); // неполная пачка — зависших событий больше нет

        return OutboxTariffEventRetryResultDto::make()
            ->setPublished($published)
            ->setFailed($failed);
    }
}
