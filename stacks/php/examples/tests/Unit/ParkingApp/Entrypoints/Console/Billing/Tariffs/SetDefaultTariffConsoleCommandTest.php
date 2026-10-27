<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Entrypoints\Console\Billing\Tariffs;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\SetDefaultTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Entrypoints\Console\Billing\Tariffs\SetDefaultTariffConsoleCommand;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Exceptions\NotFoundAppException;
use Tests\TestCase;

/**
 * Команда зарегистрирована через commands.php, передаёт ID в Command домена без скоупа парковок.
 *
 * @author Имя Фамилия <name@example.com>
 */
class SetDefaultTariffConsoleCommandTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 10;

    private TariffRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        $cacheRepository = Mockery::mock(TariffCacheRepositoryInterface::class);
        $cacheRepository->shouldNotReceive('forget');

        $command = new SetDefaultTariffCommand(
            $this->repository,
            new NoopTransactionManager(),
            $cacheRepository,
            Mockery::mock(ExceptionHandler::class),
        );
        $this->app->instance(SetDefaultTariffCommand::class, $command);
    }

    public function test_tariff_becomes_default_without_parking_scope(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($this->tariff(isDefault: true));
        $this->repository->shouldNotReceive('setDefault');

        $this->artisan('billing:tariffs:set-default', ['id' => (string) self::TARIFF_ID])
            ->expectsOutput('Тариф ' . self::TARIFF_ID . ' — по умолчанию у парковки ' . self::PARKING_ID)
            ->assertSuccessful();
    }

    public function test_invalid_id_is_rejected_without_domain_call(): void
    {
        $this->repository->shouldNotReceive('getByIdInParkings');

        $this->artisan('billing:tariffs:set-default', ['id' => 'abc'])
            ->expectsOutput('ID тарифа — целое положительное число')
            ->assertExitCode(SetDefaultTariffConsoleCommand::INVALID);
    }

    public function test_missing_tariff_exception_is_not_swallowed(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn(null);

        $this->expectException(NotFoundAppException::class);

        $this->artisan('billing:tariffs:set-default', ['id' => (string) self::TARIFF_ID])->run();
    }

    private function tariff(bool $isDefault): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->is_default = $isDefault;
        $tariff->status = TariffStatusEnum::ACTIVE;

        return $tariff;
    }
}
