<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivateDueTariffsCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivatePlannedTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Exceptions\OperationFailedAppException;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Страховка отложенной активации: одна пачка, ошибка одного тарифа — в трекер и дальше, ошибка выборки — наверх.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ActivateDueTariffsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const BATCH_SIZE = 500;

    private const PARKING_ID = 15;

    /** Тариф, который активировали параллельно между проверкой и записью */
    private const BROKEN_ID = 2;

    private TariffRepositoryInterface&MockInterface $repository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
    }

    public function test_one_failure_is_reported_and_does_not_stop_batch(): void
    {
        $this->repository
            ->shouldReceive('allDueScheduledIds')
            ->once()
            ->with(self::BATCH_SIZE)
            ->andReturn([1, self::BROKEN_ID, 3]);

        foreach ([1, self::BROKEN_ID, 3] as $id) {
            $this->repository
                ->shouldReceive('getByIdInParkings')
                ->once()
                ->with($id, null)
                ->andReturn($this->tariff($id, TariffStatusEnum::SCHEDULED));
        }

        $this->repository->shouldReceive('getDefaultByParkingId')->times(3)->with(self::PARKING_ID)->andReturn(null);
        $this->repository->shouldReceive('activateScheduled')->once()->with(1)->andReturn(true);
        $this->repository->shouldReceive('activateScheduled')->once()->with(self::BROKEN_ID)->andReturn(false);
        $this->repository->shouldReceive('activateScheduled')->once()->with(3)->andReturn(true);
        $this->exceptionHandler
            ->shouldReceive('report')
            ->once()
            ->with(Mockery::type(OperationFailedAppException::class));

        $this->assertSame(2, $this->command()->execute());
    }

    public function test_tariff_activated_by_job_in_parallel_is_skipped_without_write(): void
    {
        $this->repository->shouldReceive('allDueScheduledIds')->once()->andReturn([1]);
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(1, null)
            ->andReturn($this->tariff(1, TariffStatusEnum::ACTIVE));
        $this->repository->shouldNotReceive('activateScheduled');
        $this->exceptionHandler->shouldNotReceive('report');

        // Пропуск без ошибки — тоже обработан
        $this->assertSame(1, $this->command()->execute());
    }

    public function test_nothing_due_returns_zero(): void
    {
        $this->repository->shouldReceive('allDueScheduledIds')->once()->andReturn([]);
        $this->repository->shouldNotReceive('getByIdInParkings');
        $this->exceptionHandler->shouldNotReceive('report');

        $this->assertSame(0, $this->command()->execute());
    }

    public function test_selection_failure_stops_run(): void
    {
        $this->repository
            ->shouldReceive('allDueScheduledIds')
            ->once()
            ->andThrow(new QueryException('pgsql', 'select', [], new PDOException('Postgres недоступен')));
        $this->exceptionHandler->shouldNotReceive('report');

        $this->expectException(QueryException::class);

        $this->command()->execute();
    }

    private function command(): ActivateDueTariffsCommand
    {
        $activateCommand = new ActivatePlannedTariffCommand(
            $this->repository,
            new NoopTransactionManager(),
            Mockery::mock(TariffCacheRepositoryInterface::class)->shouldIgnoreMissing(),
            $this->exceptionHandler,
        );

        return new ActivateDueTariffsCommand($this->repository, $activateCommand, $this->exceptionHandler);
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
