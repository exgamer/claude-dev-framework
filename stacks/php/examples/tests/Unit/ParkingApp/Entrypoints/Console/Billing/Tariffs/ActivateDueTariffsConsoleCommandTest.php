<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Entrypoints\Console\Billing\Tariffs;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivateDueTariffsCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\ActivatePlannedTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Mockery;
use Mockery\MockInterface;
use PDOException;
use Tests\TestCase;

/**
 * Команда зарегистрирована через commands.php, печатает число обработанных тарифов; ошибка выборки не глотается.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ActivateDueTariffsConsoleCommandTest extends TestCase
{
    private TariffRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $exceptionHandler->shouldNotReceive('report');

        $command = new ActivateDueTariffsCommand(
            $this->repository,
            new ActivatePlannedTariffCommand(
                $this->repository,
                new NoopTransactionManager(),
                Mockery::mock(TariffCacheRepositoryInterface::class),
                $exceptionHandler,
            ),
            $exceptionHandler,
        );
        $this->app->instance(ActivateDueTariffsCommand::class, $command);
    }

    public function test_processed_count_is_printed_and_run_succeeds(): void
    {
        $this->repository->shouldReceive('allDueScheduledIds')->once()->andReturn([10, 11]);
        // Удалённые тарифы пропускаются без записи и считаются обработанными
        $this->repository->shouldReceive('getByIdInParkings')->twice()->andReturn(null);
        $this->repository->shouldNotReceive('activateScheduled');

        $this->artisan('billing:tariffs:activate-due')
            ->expectsOutput('Запланированные тарифы с наступившей датой: обработано 2')
            ->assertSuccessful();
    }

    public function test_selection_failure_is_not_swallowed(): void
    {
        $this->repository
            ->shouldReceive('allDueScheduledIds')
            ->once()
            ->andThrow(new QueryException('pgsql', 'select', [], new PDOException('Postgres недоступен')));

        $this->expectException(QueryException::class);

        $this->artisan('billing:tariffs:activate-due')->run();
    }
}
