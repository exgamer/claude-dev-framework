<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudService;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use PHPUnit\Framework\TestCase;

/**
 * Запись тарифа через TariffCrudService: инварианты, создание без статуса от вызывающего, 404, 422 при переносе тарифа по умолчанию, кеш по ID.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffCrudServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TARIFF_ID = 10;

    private const PARKING_IDS = [15, 16];

    private TariffRepositoryInterface&MockInterface $repository;

    private TariffCacheRepositoryInterface&MockInterface $cacheRepository;

    private ExceptionHandler&MockInterface $exceptionHandler;

    private TariffCrudService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
        // Промах кеша по умолчанию: тесты чтения из БД кеш не настраивают
        $this->cacheRepository = Mockery::mock(TariffCacheRepositoryInterface::class)->shouldIgnoreMissing();
        $this->exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $this->service = new TariffCrudService($this->repository, $this->cacheRepository, $this->exceptionHandler);
    }

    public function test_created_tariff_gets_initial_state_from_model(): void
    {
        $dto = $this->dto(name: 'Стандарт')->setScheduledAt(Carbon::now()->addDay());
        $tariff = new Tariff();

        // Статус и флаг не передаются: начальное состояние задаёт Tariff::initState() (TariffTest)
        $this->repository
            ->shouldReceive('createFromDto')
            ->once()
            ->with($dto)
            ->andReturn($tariff);

        $this->assertSame($tariff, $this->service->create($dto));
    }

    public function test_invalid_dto_is_rejected_without_write(): void
    {
        $this->repository->shouldNotReceive('createFromDto');

        try {
            $this->service->create($this->dto(name: '  '));
            $this->fail('Ожидался ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertArrayHasKey('name', $e->getDetails());
        }
    }

    public function test_update_of_missing_tariff_is_not_found_without_write(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn(null);
        $this->repository->shouldNotReceive('updateFromDto');

        $this->expectException(NotFoundAppException::class);

        $this->service->update(self::TARIFF_ID, null, $this->dto(name: 'Стандарт'));
    }

    public function test_find_by_id_passes_parking_scope_to_repository(): void
    {
        $tariff = new Tariff();

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, self::PARKING_IDS)
            ->andReturn($tariff);

        $this->assertSame($tariff, $this->service->findById(self::TARIFF_ID, self::PARKING_IDS));
    }

    public function test_find_by_id_of_other_parking_tariff_is_null(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, self::PARKING_IDS)
            ->andReturn(null);

        $this->assertNull($this->service->findById(self::TARIFF_ID, self::PARKING_IDS));
    }

    public function test_find_by_id_without_restriction_passes_null(): void
    {
        $tariff = new Tariff();

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff);

        $this->assertSame($tariff, $this->service->findById(self::TARIFF_ID, null));
    }

    public function test_update_of_other_parking_tariff_is_not_found_without_write(): void
    {
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, self::PARKING_IDS)
            ->andReturn(null);
        $this->repository->shouldNotReceive('updateFromDto');

        $this->expectException(NotFoundAppException::class);

        $this->service->update(self::TARIFF_ID, self::PARKING_IDS, $this->dto(name: 'Стандарт'));
    }

    public function test_cached_tariff_of_scope_parking_is_returned_without_database(): void
    {
        $cached = $this->tariff(parkingId: 15);

        $this->cacheRepository->shouldReceive('getById')->once()->with(self::TARIFF_ID)->andReturn($cached);
        $this->repository->shouldNotReceive('getByIdInParkings');

        $this->assertSame($cached, $this->service->findById(self::TARIFF_ID, self::PARKING_IDS));
    }

    public function test_cached_tariff_of_other_parking_is_null_without_database(): void
    {
        $this->cacheRepository
            ->shouldReceive('getById')
            ->once()
            ->with(self::TARIFF_ID)
            ->andReturn($this->tariff(parkingId: 99));
        $this->repository->shouldNotReceive('getByIdInParkings');

        $this->assertNull($this->service->findById(self::TARIFF_ID, self::PARKING_IDS));
    }

    public function test_cache_miss_reads_database_and_fills_cache(): void
    {
        $tariff = $this->tariff(parkingId: 15);

        $this->cacheRepository->shouldReceive('getById')->once()->with(self::TARIFF_ID)->andReturn(null);
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, self::PARKING_IDS)
            ->andReturn($tariff);
        $this->cacheRepository->shouldReceive('set')->once()->with($tariff);

        $this->assertSame($tariff, $this->service->findById(self::TARIFF_ID, self::PARKING_IDS));
    }

    public function test_unavailable_cache_is_reported_and_tariff_read_from_database(): void
    {
        $tariff = $this->tariff(parkingId: 15);
        $failure = new OperationFailedAppException('Кеш тарифов недоступен');

        $this->cacheRepository->shouldReceive('getById')->once()->andThrow($failure);
        $this->cacheRepository->shouldReceive('set')->once()->andThrow($failure);
        $this->exceptionHandler->shouldReceive('report')->twice()->with($failure);
        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff);

        $this->assertSame($tariff, $this->service->findById(self::TARIFF_ID, null));
    }

    public function test_update_forgets_cached_tariff(): void
    {
        $tariff = $this->tariff(parkingId: 15);
        $dto = $this->dto(name: 'Стандарт');

        $updated = $this->tariff(parkingId: 15);

        $this->repository
            ->shouldReceive('getByIdInParkings')
            ->twice()
            ->with(self::TARIFF_ID, null)
            ->andReturn($tariff, $updated);
        $this->repository->shouldReceive('updateFromDto')->once()->with(self::TARIFF_ID, $dto)->andReturnTrue();
        $this->cacheRepository->shouldReceive('forget')->once()->with(self::TARIFF_ID);

        $result = $this->service->update(self::TARIFF_ID, null, $dto);

        $this->assertSame($updated, $result);
    }

    public function test_moving_default_tariff_to_parking_with_default_is_validation_error(): void
    {
        $tariff = $this->tariff(parkingId: 15);
        $dto = $this->dto(name: 'Стандарт');

        $this->repository->shouldReceive('getByIdInParkings')->once()->andReturn($tariff);
        // Уникальный индекс тарифа по умолчанию: репозиторий вернул false — 422, не 500
        $this->repository->shouldReceive('updateFromDto')->once()->with(self::TARIFF_ID, $dto)->andReturnFalse();
        $this->cacheRepository->shouldNotReceive('forget');

        try {
            $this->service->update(self::TARIFF_ID, null, $dto);
            $this->fail('Ожидалось ValidationAppException');
        } catch (ValidationAppException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertArrayHasKey('parking_id', $e->getDetails());
        }
    }

    private function tariff(int $parkingId): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->parking_id = $parkingId;

        return $tariff;
    }

    private function dto(string $name): TariffDto
    {
        return TariffDto::make()
            ->setParkingId(15)
            ->setName($name)
            ->setPrice(50000);
    }
}
