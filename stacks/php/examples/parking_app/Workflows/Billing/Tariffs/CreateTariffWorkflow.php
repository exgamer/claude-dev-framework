<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\PublishOutboxTariffEventCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
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
 * Создание тарифа парковки с уведомлением партнёрского биллинга и событием tariff.created через outbox.
 *
 * @author Имя Фамилия <name@example.com>
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
     * До транзакции — от дешёвых проверок к дорогим: инварианты DTO, парковка и льготный период против её наибольшей
     * стоянки, лимит цены реестра. В транзакции — тариф и событие outbox. После коммита — сначала отложенная активация
     * запланированного тарифа, затем уведомление партнёра и первая отправка события; сбой постановки Job — report(), не наверх.
     *
     * @throws ValidationAppException
     * @throws OperationFailedAppException реестр лимитов недоступен
     * @throws Throwable
     */
    public function execute(TariffDto $dto): Tariff
    {
        $this->tariffCrudService->assertValid($dto);

        if (! $this->parkingRepository->oneById($dto->getParkingId())) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'parking_id' => ['Парковка не найдена'],
            ]);
        }

        $this->checkGraceShorterThanMaxStay($dto);
        $this->checkPriceLimit($dto);

        // Парковку отключили до коммита — тариф останется активным: риск принят
        /** @var array{Tariff, OutboxTariffEvent} $written */
        $written = $this->transactionManager->run(function () use ($dto): array {
            $tariff = $this->tariffCrudService->create($dto);

            $eventDto = OutboxTariffEventDto::make()
                ->setTariffId($tariff->id)
                ->setParkingId($dto->getParkingId())
                ->setName($dto->getName())
                ->setCurrency($dto->getCurrency())
                ->setPrice($dto->getPrice())
                ->setGraceMinutes($dto->getGraceMinutes());

            $event = $this->outboxRepository->createPending($eventDto, OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED);

            return [$tariff, $event];
        });

        [$tariff, $event] = $written;

        // Первым после коммита: сбой уведомления или отправки не должен оставить тариф без активации
        if ($tariff->canActivate()) {
            try {
                ActivatePlannedTariffJob::dispatch($tariff->id)->delay($tariff->scheduled_at);
            } catch (Throwable $e) {
                // Тариф уже записан: не поставился Job — тариф подберёт billing:tariffs:activate-due
                $this->exceptionHandler->report($e);
            }
        }

        try {
            $this->tariffNotifier->sendCreated($tariff->id, $dto);
        } catch (OperationFailedAppException $e) {
            // Отказ партнёра тариф не отменяет; повтора нет — граница примера (README)
            $this->exceptionHandler->report($e);
        }

        try {
            $this->publishOutboxEventCommand->execute($event->id);
        } catch (OperationFailedAppException $e) {
            // Событие повторит billing:outbox-tariff-events:retry
            $this->exceptionHandler->report($e);
        }

        return $tariff;
    }

    /**
     * @throws ValidationAppException
     * @throws OperationFailedAppException реестр лимитов недоступен
     */
    private function checkPriceLimit(TariffDto $dto): void
    {
        $maxPrice = $this->priceLimitRepository->getMaxPrice($dto->getParkingId(), $dto->getCurrency());

        if ($maxPrice !== null && $dto->getPrice() > $maxPrice) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'price' => ['Цена выше лимита городского реестра для этой парковки: ' . $maxPrice],
            ]);
        }
    }

    /**
     * Льготный период тарифа (минуты) короче наибольшей стоянки парковки; у парковки без ограничения — любой.
     *
     * @throws ValidationAppException
     */
    private function checkGraceShorterThanMaxStay(TariffDto $dto): void
    {
        $graceMinutes = $dto->getGraceMinutes();

        if ($graceMinutes === null) {
            return;
        }

        $maxStay = $this->parkingRepository->getMaxStayById($dto->getParkingId());

        if ($maxStay === null) {
            return;
        }

        $maxStayMinutes = $maxStay->getAmount() * $maxStay->getUnit()->minutes();

        if ($graceMinutes >= $maxStayMinutes) {
            throw new ValidationAppException('VALIDATION ERROR', [
                'grace_minutes' => ['Льготный период должен быть короче наибольшей стоянки парковки: ' . $maxStayMinutes . ' мин.'],
            ]);
        }
    }
}
