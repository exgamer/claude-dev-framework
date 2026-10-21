<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Commands;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\DeactivateParkingTariffsCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Exceptions\OperationFailedAppException;
use PHPUnit\Framework\TestCase;

/**
 * Отключение парковки: её неархивные тарифы — в архив одним условным UPDATE, кеш изменённых сбрасывается, сбой кеша — в Sentry.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class DeactivateParkingTariffsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const PARKING_ID = 15;

    private TariffRepositoryInterface&MockInterface $repository;

    private TariffCacheRepositoryInterface&MockInterface $cacheRepository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    private DeactivateParkingTariffsCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        $this->cacheRepository = Mockery::mock(TariffCacheRepositoryInterface::class);
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->command = new DeactivateParkingTariffsCommand($this->repository, $this->cacheRepository, $this->exceptionHandler);
    }

    public function test_not_archived_tariffs_are_archived_and_their_cache_forgotten(): void
    {
        $this->repository
            ->shouldReceive('allNotArchivedIdsByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn([3, 4]);
        $this->repository
            ->shouldReceive('archiveNotArchivedByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn(2);
        $this->cacheRepository->shouldReceive('forget')->once()->with(3);
        $this->cacheRepository->shouldReceive('forget')->once()->with(4);

        $this->assertSame(2, $this->command->execute(self::PARKING_ID));
    }

    public function test_cache_failure_after_write_is_reported(): void
    {
        $failure = new OperationFailedAppException('Кеш тарифов недоступен');

        $this->repository->shouldReceive('allNotArchivedIdsByParkingId')->once()->andReturn([3]);
        $this->repository->shouldReceive('archiveNotArchivedByParkingId')->once()->andReturn(1);
        $this->cacheRepository->shouldReceive('forget')->once()->with(3)->andThrow($failure);
        $this->exceptionHandler->shouldReceive('report')->once()->with($failure);

        $this->assertSame(1, $this->command->execute(self::PARKING_ID));
    }
}
