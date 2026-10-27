<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\Cache\Billing\Tariffs\Repositories;

use App\ParkingApp\Infrastructure\Cache\Billing\Tariffs\Repositories\TariffCacheRepository;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Cache\CacheManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Кеш тарифов: промах — null, строка кеша — модель; исключение драйвера на чтении, записи и сбросе — OperationFailedAppException.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffCacheRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TARIFF_ID = 42;

    private const KEY = 'tariff:42';

    private CacheManagerInterface&MockInterface $cacheManager;

    private TariffCacheRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(CacheManagerInterface::class);
        $this->cacheManager->shouldReceive('getCacheKeyByParts')->andReturn(self::KEY);
        $this->repository = new TariffCacheRepository($this->cacheManager, new Tariff());
    }

    public function test_cache_miss_is_null(): void
    {
        $this->cacheManager->shouldReceive('get')->once()->with(self::KEY)->andReturn(null);

        $this->assertNull($this->repository->getById(self::TARIFF_ID));
    }

    public function test_cached_row_becomes_model(): void
    {
        $this->cacheManager->shouldReceive('get')->once()->with(self::KEY)->andReturn(['id' => self::TARIFF_ID, 'name' => 'Стандарт']);

        $tariff = $this->repository->getById(self::TARIFF_ID);

        $this->assertSame(self::TARIFF_ID, $tariff?->id);
        $this->assertTrue($tariff->exists);
    }

    public function test_driver_error_on_read_fails(): void
    {
        // Драйверы Redis бросают разные классы — адаптер ловит общий Exception
        $this->cacheManager->shouldReceive('get')->once()->andThrow(new RuntimeException('connection refused'));

        $this->expectException(OperationFailedAppException::class);

        $this->repository->getById(self::TARIFF_ID);
    }

    public function test_driver_error_on_write_fails(): void
    {
        $this->cacheManager->shouldReceive('set')->once()->andThrow(new RuntimeException('connection refused'));
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;

        $this->expectException(OperationFailedAppException::class);

        $this->repository->set($tariff);
    }

    public function test_driver_error_on_forget_fails(): void
    {
        $this->cacheManager->shouldReceive('forget')->once()->with(self::KEY)->andThrow(new RuntimeException('connection refused'));

        $this->expectException(OperationFailedAppException::class);

        $this->repository->forget(self::TARIFF_ID);
    }
}
