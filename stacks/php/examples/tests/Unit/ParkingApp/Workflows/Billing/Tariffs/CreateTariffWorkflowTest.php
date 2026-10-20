<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Common\Enums\TimeUnitEnum;
use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Commands\PublishOutboxTariffEventCommand;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Jobs\ActivatePlannedTariffJob;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\DTO\ParkingMaxStayDto;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use App\ParkingApp\Workflows\Billing\Tariffs\CreateTariffWorkflow;
use App\ParkingApp\Workflows\Billing\Tariffs\TariffNotifierInterface;
use ArrayObject;
use Carbon\Carbon;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use MPS\Core\Models\Model;
use Tests\TestCase;

/**
 * Сценарий создания тарифа: проверки до транзакции, тариф и outbox в ней, после коммита — отправка, публикация, активация; Tests\TestCase — ради Queue::fake().
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class CreateTariffWorkflowTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 10;

    private const OUTBOX_ID = 77;

    /** Шаги сценария в порядке выполнения: проверка парковки, границы транзакции, записи, отправки после коммита */
    private ArrayObject $steps;

    private ParkingRepositoryInterface&MockInterface $parkingRepository;

    private TariffCrudServiceInterface&MockInterface $tariffCrudService;

    private OutboxTariffEventRepositoryInterface&MockInterface $outboxRepository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->steps = new ArrayObject();
        $this->parkingRepository = Mockery::mock(ParkingRepositoryInterface::class);
        $this->tariffCrudService = Mockery::mock(TariffCrudServiceInterface::class);
        $this->outboxRepository = Mockery::mock(OutboxTariffEventRepositoryInterface::class);
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
    }

    public function test_price_above_registry_limit_is_rejected_without_write(): void
    {
        $this->parkingRepository->shouldNotReceive('oneById');
        $this->parkingRepository->shouldNotReceive('getMaxStayById');
        $this->tariffCrudService->shouldNotReceive('create');
        $this->outboxRepository->shouldNotReceive('createPending');

        $workflow = $this->workflow(maxPrice: 40000, partnerFails: false, brokerFails: false);

        try {
            $workflow->execute($this->dto(price: 50000));
            $this->fail('Ожидался ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertArrayHasKey('price', $e->getDetails());
        }

        $this->assertSame([], $this->steps->getArrayCopy());
    }

    public function test_missing_parking_is_rejected_without_write(): void
    {
        $this->parkingRepository
            ->shouldReceive('oneById')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturnUsing(function (): ?Model {
                $this->steps[] = 'parking';

                return null;
            });
        $this->parkingRepository->shouldNotReceive('getMaxStayById');
        $this->tariffCrudService->shouldNotReceive('create');
        $this->outboxRepository->shouldNotReceive('createPending');

        $workflow = $this->workflow(maxPrice: null, partnerFails: false, brokerFails: false);

        try {
            $workflow->execute($this->dto(price: 50000));
            $this->fail('Ожидался ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertArrayHasKey('parking_id', $e->getDetails());
        }

        // Транзакция не открывается
        $this->assertSame(['parking'], $this->steps->getArrayCopy());
    }

    public function test_grace_not_shorter_than_parking_max_stay_is_rejected_without_write(): void
    {
        $parking = Mockery::mock(Model::class);
        $maxStay = ParkingMaxStayDto::make()
            ->setAmount(1)
            ->setUnit(TimeUnitEnum::HOUR);

        $this->parkingRepository->shouldReceive('oneById')->once()->with(self::PARKING_ID)->andReturn($parking);
        $this->parkingRepository->shouldReceive('getMaxStayById')->once()->with(self::PARKING_ID)->andReturn($maxStay);
        $this->tariffCrudService->shouldNotReceive('create');
        $this->outboxRepository->shouldNotReceive('createPending');

        $workflow = $this->workflow(maxPrice: null, partnerFails: false, brokerFails: false);
        $dto = $this->dto(price: 50000)->setGraceMinutes(60);

        try {
            $workflow->execute($dto);
            $this->fail('Ожидался ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertArrayHasKey('grace_minutes', $e->getDetails());
        }

        // Транзакция не открывается
        $this->assertSame([], $this->steps->getArrayCopy());
    }

    public function test_grace_shorter_than_parking_max_stay_creates_tariff(): void
    {
        $maxStay = ParkingMaxStayDto::make()
            ->setAmount(1)
            ->setUnit(TimeUnitEnum::HOUR);

        $this->expectTariffCreated(TariffStatusEnum::ACTIVE, scheduledAt: null);
        $this->parkingRepository->shouldReceive('getMaxStayById')->once()->with(self::PARKING_ID)->andReturn($maxStay);
        $this->expectOutboxEventWritten();
        $this->outboxRepository->shouldReceive('markPublished')->once()->andReturn(true);
        $this->exceptionHandler->shouldNotReceive('report');

        $workflow = $this->workflow(maxPrice: null, partnerFails: false, brokerFails: false);
        $dto = $this->dto(price: 50000)->setGraceMinutes(59);
        $tariff = $workflow->execute($dto);

        $this->assertSame(self::TARIFF_ID, $tariff->id);
    }

    public function test_outbox_event_is_written_in_tariff_transaction_and_published_after_commit(): void
    {
        $this->expectTariffCreated(TariffStatusEnum::ACTIVE, scheduledAt: null);
        $this->expectOutboxEventWritten();
        $this->outboxRepository
            ->shouldReceive('markPublished')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(true);
        $this->exceptionHandler->shouldNotReceive('report');

        $workflow = $this->workflow(maxPrice: 50000, partnerFails: false, brokerFails: false);
        $tariff = $workflow->execute($this->dto(price: 50000));

        $this->assertSame(self::TARIFF_ID, $tariff->id);
        $this->assertSame([
            'parking',
            'tx:begin',
            'create',
            'outbox:' . OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED->value . ':' . self::TARIFF_ID,
            'tx:commit',
            'send:' . self::TARIFF_ID,
            'publish:' . self::OUTBOX_ID,
        ], $this->steps->getArrayCopy());
        Queue::assertNothingPushed();
    }

    public function test_partner_failure_is_reported_and_tariff_stays_created(): void
    {
        $this->expectTariffCreated(TariffStatusEnum::ACTIVE, scheduledAt: null);
        $this->expectOutboxEventWritten();
        $this->outboxRepository->shouldReceive('markPublished')->once()->andReturn(true);
        $this->exceptionHandler
            ->shouldReceive('report')
            ->once()
            ->with(Mockery::type(OperationFailedAppException::class));

        $workflow = $this->workflow(maxPrice: null, partnerFails: true, brokerFails: false);
        $tariff = $workflow->execute($this->dto(price: 50000));

        $this->assertSame(self::TARIFF_ID, $tariff->id);
        $this->assertContains('publish:' . self::OUTBOX_ID, $this->steps->getArrayCopy());
    }

    public function test_broker_failure_is_reported_and_event_awaits_retry(): void
    {
        $this->expectTariffCreated(TariffStatusEnum::ACTIVE, scheduledAt: null);
        $this->expectOutboxEventWritten();
        $this->outboxRepository->shouldNotReceive('markPublished');
        $this->outboxRepository
            ->shouldReceive('incrementAttempts')
            ->once()
            ->with(self::OUTBOX_ID)
            ->andReturn(1);
        $this->outboxRepository
            ->shouldReceive('markAwaitingRetry')
            ->once()
            ->with(self::OUTBOX_ID);
        $this->exceptionHandler
            ->shouldReceive('report')
            ->once()
            ->with(Mockery::type(OperationFailedAppException::class));

        $workflow = $this->workflow(maxPrice: null, partnerFails: false, brokerFails: true);
        $tariff = $workflow->execute($this->dto(price: 50000));

        $this->assertSame(self::TARIFF_ID, $tariff->id);
    }

    public function test_scheduled_tariff_activation_is_dispatched_at_scheduled_time_after_commit(): void
    {
        $scheduledAt = Carbon::now()->addDay()->startOfSecond();

        $this->expectTariffCreated(TariffStatusEnum::SCHEDULED, $scheduledAt);
        $this->expectOutboxEventWritten();
        $this->outboxRepository->shouldReceive('markPublished')->once()->andReturn(true);
        $this->exceptionHandler->shouldNotReceive('report');

        $workflow = $this->workflow(maxPrice: null, partnerFails: false, brokerFails: false);
        $dto = $this->dto(price: 50000);
        $dto->setScheduledAt($scheduledAt);
        $workflow->execute($dto);

        Queue::assertPushed(
            ActivatePlannedTariffJob::class,
            static fn (ActivatePlannedTariffJob $job): bool => $job->getTariffId() === self::TARIFF_ID
                && $job->delay == $scheduledAt,
        );
    }

    private function expectTariffCreated(TariffStatusEnum $status, ?Carbon $scheduledAt): void
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->status = $status;
        $tariff->scheduled_at = $scheduledAt;

        $parking = Mockery::mock(Model::class);

        $this->parkingRepository
            ->shouldReceive('oneById')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturnUsing(function () use ($parking): Model {
                $this->steps[] = 'parking';

                return $parking;
            });

        $this->tariffCrudService
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function () use ($tariff): Tariff {
                $this->steps[] = 'create';

                return $tariff;
            });
    }

    private function expectOutboxEventWritten(): void
    {
        $event = new OutboxTariffEvent();
        $event->id = self::OUTBOX_ID;
        $event->tariff_id = self::TARIFF_ID;
        $event->parking_id = self::PARKING_ID;
        $event->name = 'Стандарт';
        $event->currency = CurrencyEnum::KZT;
        $event->price = 50000;
        $event->grace_minutes = null;
        $event->routing_key = OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED;
        $event->status = OutboxTariffEventStatusEnum::PENDING;

        $this->outboxRepository
            ->shouldReceive('createPending')
            ->once()
            ->andReturnUsing(function (OutboxTariffEventDto $dto, OutboxTariffEventRoutingKeyEnum $routingKey) use ($event): OutboxTariffEvent {
                $this->steps[] = 'outbox:' . $routingKey->value . ':' . $dto->getTariffId();

                return $event;
            });

        $this->outboxRepository
            ->shouldReceive('oneById')
            ->with(self::OUTBOX_ID)
            ->andReturn($event);
    }

    private function workflow(?int $maxPrice, bool $partnerFails, bool $brokerFails): CreateTariffWorkflow
    {
        $transactionManager = new class ($this->steps) implements TransactionManagerInterface {
            public function __construct(
                private readonly ArrayObject $steps,
            ) {
            }

            public function run(callable $callback, int $attempts = 1)
            {
                $this->steps[] = 'tx:begin';
                $result = $callback();
                $this->steps[] = 'tx:commit';

                return $result;
            }
        };

        $publisher = new class ($this->steps, $brokerFails) implements OutboxTariffEventPublisherInterface {
            public function __construct(
                private readonly ArrayObject $steps,
                private readonly bool $fails,
            ) {
            }

            public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void
            {
                if ($this->fails) {
                    throw new OperationFailedAppException('RabbitMQ недоступен');
                }

                $this->steps[] = 'publish:' . $eventId;
            }
        };

        return new CreateTariffWorkflow(
            new class ($maxPrice) implements PriceLimitRepositoryInterface {
                public function __construct(
                    private readonly ?int $maxPrice,
                ) {
                }

                public function getMaxPrice(int $parkingId, CurrencyEnum $currency): ?int
                {
                    return $this->maxPrice;
                }
            },
            $this->parkingRepository,
            $this->tariffCrudService,
            $transactionManager,
            new class ($this->steps, $partnerFails) implements TariffNotifierInterface {
                public function __construct(
                    private readonly ArrayObject $steps,
                    private readonly bool $fails,
                ) {
                }

                public function sendCreated(int $tariffId, TariffDto $dto): void
                {
                    if ($this->fails) {
                        throw new OperationFailedAppException('Партнёрский биллинг недоступен');
                    }

                    $this->steps[] = 'send:' . $tariffId;
                }
            },
            $this->outboxRepository,
            new PublishOutboxTariffEventCommand(
                $this->outboxRepository,
                $publisher,
                $transactionManager,
                new Config(['parking-app' => ['outbox_tariff_events' => ['max_attempts' => 5]]]),
            ),
            $this->exceptionHandler,
        );
    }

    private function dto(int $price): TariffDto
    {
        return TariffDto::make()
            ->setParkingId(self::PARKING_ID)
            ->setName('Стандарт')
            ->setPrice($price);
    }
}
