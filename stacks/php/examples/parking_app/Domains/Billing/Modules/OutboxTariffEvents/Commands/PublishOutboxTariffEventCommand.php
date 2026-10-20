<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use Illuminate\Contracts\Config\Repository as Config;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use Throwable;

/**
 * Отправка одного outbox-события о тарифе в брокер с учётом попыток.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class PublishOutboxTariffEventCommand
{
    private readonly int $maxAttempts;

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(
        private readonly OutboxTariffEventRepositoryInterface $repository,
        private readonly OutboxTariffEventPublisherInterface $publisher,
        private readonly TransactionManagerInterface $transactionManager,
        Config $config,
    ) {
        $maxAttempts = $config->get('parking-app.outbox_tariff_events.max_attempts');

        if (! is_numeric($maxAttempts) || (int) $maxAttempts <= 0) {
            throw new InvalidConfigurationAppException('Не задан OUTBOX_TARIFF_EVENTS_MAX_ATTEMPTS');
        }

        $this->maxAttempts = (int) $maxAttempts;
    }

    /**
     * Опубликованное, исчерпавшее попытки и отсутствующее событие не отправляет — повтор безопасен.
     *
     * @throws OperationFailedAppException событие не опубликовано: попытка записана, статус — AWAITING_RETRY
     *                                     или FAILED по лимиту попыток
     * @throws Throwable
     */
    public function execute(int $outboxId): void
    {
        /** @var ?OutboxTariffEvent $event */
        $event = $this->repository->oneById($outboxId);

        if (! $event || ! $event->status->isDeliverable()) {
            return;
        }

        try {
            // Упали между публикацией и markPublished — событие уйдёт ещё раз, получатель отсекает дубль по event_id
            $this->publisher->publish($event->id, $event->routing_key, $this->eventDto($event));
        } catch (OperationFailedAppException $e) {
            $attempts = $this->recordFailedAttempt($event->id);

            if ($attempts >= $this->maxAttempts) {
                throw new OperationFailedAppException(
                    "Событие о тарифе не опубликовано за {$attempts} попыток, нужен ручной разбор: " . $e->getMessage(),
                    ['outbox_id' => $event->id, 'attempts' => $attempts],
                );
            }

            throw $e;
        }

        $this->repository->markPublished($event->id);
    }

    /**
     * Попытка и статус — вместе: счётчик без статуса дал бы FAILED по числу, но AWAITING_RETRY в выборке повтора.
     *
     * @throws Throwable
     */
    private function recordFailedAttempt(int $id): int
    {
        return $this->transactionManager->run(function () use ($id): int {
            $attempts = $this->repository->incrementAttempts($id);

            if ($attempts >= $this->maxAttempts) {
                $this->repository->markFailed($id);

                return $attempts;
            }

            $this->repository->markAwaitingRetry($id);

            return $attempts;
        });
    }

    private function eventDto(OutboxTariffEvent $event): OutboxTariffEventDto
    {
        return OutboxTariffEventDto::make()
            ->setTariffId($event->tariff_id)
            ->setParkingId($event->parking_id)
            ->setName($event->name)
            ->setCurrency($event->currency)
            ->setPrice($event->price)
            ->setGraceMinutes($event->grace_minutes);
    }
}
