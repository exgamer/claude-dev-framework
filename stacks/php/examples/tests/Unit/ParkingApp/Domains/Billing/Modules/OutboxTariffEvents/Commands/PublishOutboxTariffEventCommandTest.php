<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\PublishOutboxTariffEventCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use ArrayObject;
use Illuminate\Config\Repository as Config;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use PHPUnit\Framework\TestCase;

/**
 * Отправка outbox-события: доставленное и исчерпавшее попытки не отправляется, сбой брокера — AWAITING_RETRY, на лимите — FAILED.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PublishOutboxTariffEventCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const OUTBOX_ID = 77;

    private const TARIFF_ID = 10;

    private const MAX_ATTEMPTS = 3;

    private OutboxTariffEventRepositoryInterface&MockInterface $repository;

    /** Опубликованные событием ID: event_id, routing key, tariff_id */
    private ArrayObject $published;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(OutboxTariffEventRepositoryInterface::class);
        $this->published = new ArrayObject();
    }

    public function test_pending_event_is_published_and_marked_published(): void
    {
        $this->expectEvent(OutboxTariffEventStatusEnum::PENDING);
        $this->repository
            ->shouldReceive('markPublished')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(true);
        $this->repository->shouldNotReceive('incrementAttempts');

        $command = $this->command(brokerFails: false);
        $command->execute(self::OUTBOX_ID);

        $this->assertSame(
            [self::OUTBOX_ID . ':tariff.created:' . self::TARIFF_ID],
            $this->published->getArrayCopy(),
        );
    }

    public function test_published_and_failed_events_are_not_sent_again(): void
    {
        foreach ([OutboxTariffEventStatusEnum::PUBLISHED, OutboxTariffEventStatusEnum::FAILED] as $status) {
            $this->repository = Mockery::mock(OutboxTariffEventRepositoryInterface::class);
            $this->expectEvent($status);
            $this->repository->shouldNotReceive('markPublished');
            $this->repository->shouldNotReceive('incrementAttempts');

            $command = $this->command(brokerFails: false);
            $command->execute(self::OUTBOX_ID);
        }

        $this->assertSame([], $this->published->getArrayCopy());
    }

    public function test_missing_event_is_skipped(): void
    {
        $this->repository
            ->shouldReceive('oneById')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(null);
        $this->repository->shouldNotReceive('markPublished');

        $command = $this->command(brokerFails: false);
        $command->execute(self::OUTBOX_ID);

        $this->assertSame([], $this->published->getArrayCopy());
    }

    public function test_broker_failure_below_limit_awaits_retry_and_is_rethrown(): void
    {
        $this->expectEvent(OutboxTariffEventStatusEnum::AWAITING_RETRY);
        $this->repository
            ->shouldReceive('incrementAttempts')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(self::MAX_ATTEMPTS - 1);
        $this->repository
            ->shouldReceive('markAwaitingRetry')
            ->once()
            ->with(self::OUTBOX_ID);
        $this->repository->shouldNotReceive('markFailed');
        $this->repository->shouldNotReceive('markPublished');

        $this->expectException(OperationFailedAppException::class);
        $this->expectExceptionMessage('RabbitMQ недоступен');

        $command = $this->command(brokerFails: true);
        $command->execute(self::OUTBOX_ID);
    }

    public function test_broker_failure_on_last_attempt_marks_failed_for_manual_review(): void
    {
        $this->expectEvent(OutboxTariffEventStatusEnum::AWAITING_RETRY);
        $this->repository
            ->shouldReceive('incrementAttempts')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(self::MAX_ATTEMPTS);
        $this->repository
            ->shouldReceive('markFailed')
            ->once()
            ->with(self::OUTBOX_ID);
        $this->repository->shouldNotReceive('markAwaitingRetry');

        try {
            $command = $this->command(brokerFails: true);
            $command->execute(self::OUTBOX_ID);
            $this->fail('Ожидался OperationFailedAppException');
        } catch (OperationFailedAppException $e) {
            $this->assertSame(
                ['outbox_id' => self::OUTBOX_ID, 'attempts' => self::MAX_ATTEMPTS],
                $e->getDetails(),
            );
        }
    }

    public function test_missing_max_attempts_config_is_rejected(): void
    {
        $this->expectException(InvalidConfigurationAppException::class);

        new PublishOutboxTariffEventCommand(
            $this->repository,
            $this->publisher(fails: false),
            new NoopTransactionManager(),
            new Config([]),
        );
    }

    private function expectEvent(OutboxTariffEventStatusEnum $status): void
    {
        $event = new OutboxTariffEvent();
        $event->id = self::OUTBOX_ID;
        $event->tariff_id = self::TARIFF_ID;
        $event->parking_id = 15;
        $event->name = 'Стандарт';
        $event->currency = CurrencyEnum::KZT;
        $event->price = 50000;
        $event->grace_minutes = 15;
        $event->routing_key = OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED;
        $event->status = $status;

        $this->repository
            ->shouldReceive('oneById')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn($event);
    }

    private function command(bool $brokerFails): PublishOutboxTariffEventCommand
    {
        return new PublishOutboxTariffEventCommand(
            $this->repository,
            $this->publisher($brokerFails),
            new NoopTransactionManager(),
            new Config(['parking-app' => ['outbox_tariff_events' => ['max_attempts' => self::MAX_ATTEMPTS]]]),
        );
    }

    private function publisher(bool $fails): OutboxTariffEventPublisherInterface
    {
        return new class ($this->published, $fails) implements OutboxTariffEventPublisherInterface {
            public function __construct(
                private readonly ArrayObject $published,
                private readonly bool $fails,
            ) {
            }

            public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void
            {
                if ($this->fails) {
                    throw new OperationFailedAppException('RabbitMQ недоступен');
                }

                $this->published[] = $eventId . ':' . $routingKey->value . ':' . $dto->getTariffId();
            }
        };
    }
}
