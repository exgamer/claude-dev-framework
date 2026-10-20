<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Jobs;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivatePlannedTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Jobs\ActivatePlannedTariffJob;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Exceptions\OperationFailedAppException;
use PHPUnit\Framework\TestCase;

/**
 * Отложенная активация: запланированный тариф — действующим и по умолчанию, прежний — в архив, повтор — без записи.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ActivatePlannedTariffJobTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 10;

    private const PREVIOUS_DEFAULT_ID = 9;

    private TariffRepositoryInterface&MockInterface $repository;

    private TariffCacheRepositoryInterface&MockInterface $cacheRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        // Сброс кеша проверяется там, где запись была; в остальных тестах — не важен
        $this->cacheRepository = Mockery::mock(TariffCacheRepositoryInterface::class)->shouldIgnoreMissing();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_runs_on_database_connection_with_retries(): void
    {
        $job = new ActivatePlannedTariffJob(self::TARIFF_ID);

        $this->assertSame('database', $job->connection);
        $this->assertSame(3, $job->tries);
        $this->assertSame(self::TARIFF_ID, $job->getTariffId());
    }

    public function test_scheduled_tariff_becomes_default_and_previous_is_archived(): void
    {
        $this->expectTariff(TariffStatusEnum::SCHEDULED);
        $this->repository
            ->shouldReceive('getDefaultByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn($this->tariff(self::PREVIOUS_DEFAULT_ID, TariffStatusEnum::ACTIVE));
        $this->repository
            ->shouldReceive('archive')
            ->once()
            ->with(self::PREVIOUS_DEFAULT_ID)
            ->ordered();
        $this->repository
            ->shouldReceive('activateScheduled')
            ->once()
            ->with(self::TARIFF_ID)
            ->andReturn(true)
            ->ordered();
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::PREVIOUS_DEFAULT_ID);
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::TARIFF_ID);

        $this->handle();
    }

    public function test_scheduled_tariff_of_parking_without_default_is_activated(): void
    {
        $this->expectTariff(TariffStatusEnum::SCHEDULED);
        $this->repository->shouldReceive('getDefaultByParkingId')->once()->andReturn(null);
        $this->repository->shouldNotReceive('archive');
        $this->repository->shouldReceive('activateScheduled')->once()->with(self::TARIFF_ID)->andReturn(true);

        $this->handle();
    }

    public function test_repeat_after_activation_or_deactivation_writes_nothing(): void
    {
        foreach ([TariffStatusEnum::ACTIVE, TariffStatusEnum::ARCHIVED] as $status) {
            $this->repository = Mockery::mock(TariffRepositoryInterface::class);
            $this->expectTariff($status);
            $this->repository->shouldNotReceive('archive');
            $this->repository->shouldNotReceive('activateScheduled');

            $this->handle();
        }
    }

    public function test_deleted_tariff_is_skipped(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn(null);
        $this->repository->shouldNotReceive('activateScheduled');

        $this->handle();
    }

    public function test_parallel_change_between_check_and_write_rolls_back(): void
    {
        $this->expectTariff(TariffStatusEnum::SCHEDULED);
        $this->repository->shouldReceive('getDefaultByParkingId')->once()->andReturn(null);
        $this->repository->shouldReceive('activateScheduled')->once()->with(self::TARIFF_ID)->andReturn(false);

        $this->expectException(OperationFailedAppException::class);

        $this->handle();
    }

    private function handle(): void
    {
        $job = new ActivatePlannedTariffJob(self::TARIFF_ID);

        $command = new ActivatePlannedTariffCommand(
            $this->repository,
            new NoopTransactionManager(),
            $this->cacheRepository,
            Mockery::mock(ExceptionHandler::class),
        );

        $job->handle($command);
    }

    private function expectTariff(TariffStatusEnum $status): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($this->tariff(self::TARIFF_ID, $status));
    }

    private function tariff(int $id, TariffStatusEnum $status): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = $id;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->status = $status;

        return $tariff;
    }
}
