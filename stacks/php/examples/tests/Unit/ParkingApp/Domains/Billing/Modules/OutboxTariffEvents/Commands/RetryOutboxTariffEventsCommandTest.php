<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\PublishOutboxTariffEventCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\RetryOutboxTariffEventsCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;
use PDOException;
use PHPUnit\Framework\TestCase;
use ValueError;

/**
 * Повтор зависших событий: пачки, сбой или ошибка одного события — в трекер и дальше, ошибка выборки — наверх.
 *
 * @author Имя Фамилия <name@example.com>
 */
class RetryOutboxTariffEventsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const BATCH_SIZE = 2;

    private const STALE_AFTER_SECONDS = 300;

    /** Событие, на котором брокер отказывает */
    private const BROKEN_ID = 2;

    /** Событие, на котором публикатор падает не сбоем брокера */
    public const POISON_ID = 9;

    private OutboxTariffEventRepositoryInterface&MockInterface $repository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(OutboxTariffEventRepositoryInterface::class);
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
    }

    public function test_batches_are_claimed_until_partial_and_one_failure_does_not_stop_run(): void
    {
        $this->repository
            ->shouldReceive('claimForRetry')
            ->with(self::BATCH_SIZE, self::STALE_AFTER_SECONDS)
            ->twice()
            ->andReturn([1, self::BROKEN_ID], [3]);

        foreach ([1, self::BROKEN_ID, 3] as $id) {
            $this->repository->shouldReceive('oneById')->once()->with($id)->andReturn($this->event($id));
        }

        $this->repository->shouldReceive('markPublished')->once()->with(1)->andReturn(true);
        $this->repository->shouldReceive('markPublished')->once()->with(3)->andReturn(true);
        $this->repository->shouldReceive('incrementAttempts')->once()->with(self::BROKEN_ID)->andReturn(1);
        $this->repository->shouldReceive('markAwaitingRetry')->once()->with(self::BROKEN_ID);
        $this->exceptionHandler
            ->shouldReceive('report')
            ->once()
            ->with(Mockery::type(OperationFailedAppException::class));

        $command = $this->command();
        $result = $command->execute();

        $this->assertSame(2, $result->getPublished());
        $this->assertSame(1, $result->getFailed());
    }

    public function test_poison_event_does_not_stop_run(): void
    {
        $this->repository
            ->shouldReceive('claimForRetry')
            ->twice()
            ->andReturn([self::POISON_ID, 3], []);
        $this->repository->shouldReceive('oneById')->once()->with(self::POISON_ID)->andReturn($this->event(self::POISON_ID));
        $this->repository->shouldReceive('oneById')->once()->with(3)->andReturn($this->event(3));
        $this->repository->shouldReceive('markPublished')->once()->with(3)->andReturn(true);
        $this->exceptionHandler
            ->shouldReceive('report')
            ->once()
            ->with(Mockery::type(ValueError::class));

        $command = $this->command();
        $result = $command->execute();

        $this->assertSame(1, $result->getPublished());
        $this->assertSame(1, $result->getFailed());
    }

    public function test_nothing_stale_finishes_after_one_claim(): void
    {
        $this->repository
            ->shouldReceive('claimForRetry')
            ->once()
            ->andReturn([]);
        $this->repository->shouldNotReceive('oneById');
        $this->exceptionHandler->shouldNotReceive('report');

        $command = $this->command();
        $result = $command->execute();

        $this->assertSame(0, $result->getPublished());
        $this->assertSame(0, $result->getFailed());
    }

    public function test_claim_failure_stops_run(): void
    {
        $this->repository
            ->shouldReceive('claimForRetry')
            ->once()
            ->andThrow(new QueryException('pgsql', 'select', [], new PDOException('Postgres недоступен')));
        $this->exceptionHandler->shouldNotReceive('report');

        $this->expectException(QueryException::class);

        $command = $this->command();
        $command->execute();
    }

    private function command(): RetryOutboxTariffEventsCommand
    {
        $config = new Config(['parking-app' => ['outbox_tariff_events' => [
            'max_attempts' => 5,
            'retry_batch_size' => self::BATCH_SIZE,
            'retry_stale_after_seconds' => self::STALE_AFTER_SECONDS,
        ]]]);

        $publisher = new class (self::BROKEN_ID) implements OutboxTariffEventPublisherInterface {
            public function __construct(
                private readonly int $brokenId,
            ) {
            }

            public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void
            {
                if ($eventId === $this->brokenId) {
                    throw new OperationFailedAppException('RabbitMQ недоступен');
                }

                if ($eventId === RetryOutboxTariffEventsCommandTest::POISON_ID) {
                    throw new ValueError('Тело события не сериализуется');
                }
            }
        };

        return new RetryOutboxTariffEventsCommand(
            $this->repository,
            new PublishOutboxTariffEventCommand($this->repository, $publisher, new NoopTransactionManager(), $config),
            $this->exceptionHandler,
            $config,
        );
    }

    private function event(int $id): OutboxTariffEvent
    {
        $event = new OutboxTariffEvent();
        $event->id = $id;
        $event->tariff_id = 10 + $id;
        $event->parking_id = 15;
        $event->name = 'Стандарт';
        $event->currency = CurrencyEnum::KZT;
        $event->price = 50000;
        $event->grace_minutes = null;
        $event->routing_key = OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED;
        $event->status = OutboxTariffEventStatusEnum::AWAITING_RETRY;

        return $event;
    }
}
