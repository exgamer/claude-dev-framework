<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\SetDefaultTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use PHPUnit\Framework\TestCase;

/**
 * Тариф по умолчанию у парковки один: прежний теряет флаг; кеш обоих сбрасывается после записи, сбой кеша — в Sentry.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class SetDefaultTariffCommandTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 10;

    private const PREVIOUS_TARIFF_ID = 7;

    private TariffRepositoryInterface&MockInterface $repository;

    private TariffCacheRepositoryInterface&MockInterface $cacheRepository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    private SetDefaultTariffCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        $this->cacheRepository = Mockery::mock(TariffCacheRepositoryInterface::class);
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->command = new SetDefaultTariffCommand(
            $this->repository,
            new NoopTransactionManager(),
            $this->cacheRepository,
            $this->exceptionHandler,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_previous_default_loses_flag_and_tariff_becomes_default(): void
    {
        $tariff = $this->tariff(self::TARIFF_ID, isDefault: false);
        $updated = $this->tariff(self::TARIFF_ID, isDefault: true);
        $previous = $this->tariff(self::PREVIOUS_TARIFF_ID, isDefault: true);

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->twice()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff, $updated);
        $this->repository
            ->shouldReceive('getDefaultByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn($previous);
        $this->repository
            ->shouldReceive('setDefault')
            ->once()
            ->with(self::PREVIOUS_TARIFF_ID, false)
            ->ordered()
            ->andReturn(true);
        $this->repository
            ->shouldReceive('setDefault')
            ->once()
            ->with(self::TARIFF_ID, true)
            ->ordered()
            ->andReturn(true);
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::PREVIOUS_TARIFF_ID);
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::TARIFF_ID);

        $this->assertSame($updated, $this->command->execute(self::TARIFF_ID, null));
    }

    public function test_cache_failure_after_write_is_reported_and_tariff_returned(): void
    {
        $tariff = $this->tariff(self::TARIFF_ID, isDefault: false);
        $updated = $this->tariff(self::TARIFF_ID, isDefault: true);
        $failure = new OperationFailedAppException('Кеш тарифов недоступен');

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->twice()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff, $updated);
        $this->repository->shouldReceive('getDefaultByParkingId')->once()->andReturn(null);
        $this->repository->shouldReceive('setDefault')->once()->with(self::TARIFF_ID, true)->andReturn(true);
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::TARIFF_ID)->andThrow($failure);
        $this->exceptionHandler->shouldReceive('report')->once()->with($failure);

        $this->assertSame($updated, $this->command->execute(self::TARIFF_ID, null));
    }

    public function test_unique_index_violation_is_validation_error_without_reread(): void
    {
        $tariff = $this->tariff(self::TARIFF_ID, isDefault: false);
        $previous = $this->tariff(self::PREVIOUS_TARIFF_ID, isDefault: true);

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff);
        $this->repository
            ->shouldReceive('getDefaultByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn($previous);
        $this->repository
            ->shouldReceive('setDefault')
            ->once()
            ->with(self::PREVIOUS_TARIFF_ID, false)
            ->andReturn(true);
        // Параллельный запрос успел назначить другой тариф: уникальный индекс отклонил запись
        $this->repository
            ->shouldReceive('setDefault')
            ->once()
            ->with(self::TARIFF_ID, true)
            ->andReturn(false);
        $this->cacheRepository->shouldNotReceive('forget');

        try {
            $this->command->execute(self::TARIFF_ID, null);
            $this->fail('Ожидалось ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertArrayHasKey('is_default', $e->getDetails());
        }
    }

    public function test_default_tariff_is_returned_without_writes(): void
    {
        $tariff = $this->tariff(self::TARIFF_ID, isDefault: true);

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff);
        $this->repository->shouldNotReceive('setDefault');

        $this->assertSame($tariff, $this->command->execute(self::TARIFF_ID, null));
    }

    public function test_missing_tariff_is_not_found_without_writes(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn(null);
        $this->repository->shouldNotReceive('setDefault');

        $this->expectException(NotFoundAppException::class);

        $this->command->execute(self::TARIFF_ID, null);
    }

    public function test_other_parking_tariff_is_not_found_without_writes(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, [self::PARKING_ID + 1])
            ->andReturn(null);
        $this->repository->shouldNotReceive('setDefault');

        $this->expectException(NotFoundAppException::class);

        $this->command->execute(self::TARIFF_ID, [self::PARKING_ID + 1]);
    }

    private function tariff(int $id, bool $isDefault): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = $id;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->is_default = $isDefault;

        return $tariff;
    }
}
