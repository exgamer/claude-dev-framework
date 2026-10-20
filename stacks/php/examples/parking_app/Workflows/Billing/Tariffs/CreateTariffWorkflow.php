<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\PublishOutboxTariffEventCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Jobs\ActivatePlannedTariffJob;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use Throwable;

/**
 * Создание тарифа парковки: лимит цены реестра, существование парковки (Catalog), уведомление партнёрского биллинга,
 * событие tariff.created в брокер через outbox, отложенная активация запланированного тарифа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class CreateTariffWorkflow
{
    public function __construct(
        private readonly PriceLimitRepositoryInterface $priceLimitRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly TariffCrudServiceInterface $tariffCrudService,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly TariffNotifierInterface $tariffNotifier,
        private readonly OutboxTariffEventRepositoryInterface $outboxRepository,
        private readonly PublishOutboxTariffEventCommand $publishOutboxEventCommand,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    /**
     * @throws ValidationAppException
     * @throws OperationFailedAppException реестр лимитов недоступен
     * @throws Throwable
     */
    public function execute(TariffDto $dto): Tariff
    {
        $maxPrice = $this->priceLimitRepository->getMaxPrice($dto->getParkingId(), $dto->getCurrency());

        if ($maxPrice !== null && $dto->getPrice() > $maxPrice) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'price' => ['Цена выше лимита городского реестра для этой парковки: ' . $maxPrice],
            ]);
        }

        /** @var array{Tariff, OutboxTariffEvent} $written */
        $written = $this->transactionManager->run(function () use ($dto): array {
            if (! $this->parkingRepository->oneById($dto->getParkingId())) {
                throw new ValidationAppException('VALIDATION ERROR', [
                    'parking_id' => ['Парковка не найдена'],
                ]);
            }

            $tariff = $this->tariffCrudService->create($dto);

            // Событие — в той же транзакции, что и тариф: нет тарифа без события и события без тарифа
            $event = $this->outboxRepository->createPending(
                OutboxTariffEventDto::make()
                    ->setTariffId($tariff->id)
                    ->setParkingId($dto->getParkingId())
                    ->setName($dto->getName())
                    ->setCurrency($dto->getCurrency())
                    ->setPrice($dto->getPrice())
                    ->setGraceMinutes($dto->getGraceMinutes()),
                OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED,
            );

            return [$tariff, $event];
        });

        [$tariff, $event] = $written;

        try {
            $this->tariffNotifier->sendCreated($tariff->id, $dto);
        } catch (OperationFailedAppException $e) {
            // Тариф уже записан, отказ партнёра его не отменяет. Повтора нет: понадобится гарантия доставки — outbox
            $this->exceptionHandler->report($e);
        }

        try {
            // Первая попытка — сразу после коммита; сбой не отменяет создание: событие повторит консольная команда
            $this->publishOutboxEventCommand->execute($event->id);
        } catch (OperationFailedAppException $e) {
            $this->exceptionHandler->report($e);
        }

        if ($tariff->status === TariffStatusEnum::SCHEDULED) {
            ActivatePlannedTariffJob::dispatch($tariff->id)->delay($tariff->scheduled_at);
        }

        return $tariff;
    }
}
