<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Queries;

use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Queries\GetParkingDefaultTariffQuery;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffEventStatusRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Тариф по умолчанию парковки со статусом последнего события о нём; нет тарифа — нет результата.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class GetParkingDefaultTariffQueryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const PARKING_ID = 15;

    private const TARIFF_ID = 10;

    private TariffRepositoryInterface&MockInterface $tariffRepository;

    private TariffEventStatusRepositoryInterface&MockInterface $eventStatusRepository;

    private GetParkingDefaultTariffQuery $query;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tariffRepository = Mockery::mock(TariffRepositoryInterface::class);
        $this->eventStatusRepository = Mockery::mock(TariffEventStatusRepositoryInterface::class);
        $this->query = new GetParkingDefaultTariffQuery($this->tariffRepository, $this->eventStatusRepository);
    }

    public function test_default_tariff_comes_with_last_event_status(): void
    {
        $tariff = $this->tariff();

        $this->tariffRepository->shouldReceive('getDefaultByParkingId')->once()->with(self::PARKING_ID)->andReturn($tariff);
        $this->eventStatusRepository->shouldReceive('getLatestStatusByTariffId')
            ->once()
            ->with(self::TARIFF_ID)
            ->andReturn(OutboxTariffEventStatusEnum::AWAITING_RETRY);

        $result = $this->query->execute(self::PARKING_ID);

        $this->assertNotNull($result);
        $this->assertSame($tariff, $result->getTariff());
        $this->assertSame(OutboxTariffEventStatusEnum::AWAITING_RETRY, $result->getLastEventStatus());
    }

    public function test_default_tariff_without_events_has_no_status(): void
    {
        $tariff = $this->tariff();

        $this->tariffRepository->shouldReceive('getDefaultByParkingId')->once()->with(self::PARKING_ID)->andReturn($tariff);
        $this->eventStatusRepository->shouldReceive('getLatestStatusByTariffId')->once()->with(self::TARIFF_ID)->andReturnNull();

        $result = $this->query->execute(self::PARKING_ID);

        $this->assertNotNull($result);
        $this->assertSame($tariff, $result->getTariff());
        $this->assertNull($result->getLastEventStatus());
    }

    public function test_parking_without_default_tariff_returns_null_and_skips_events(): void
    {
        $this->tariffRepository->shouldReceive('getDefaultByParkingId')->once()->with(self::PARKING_ID)->andReturnNull();
        $this->eventStatusRepository->shouldNotReceive('getLatestStatusByTariffId');

        $this->assertNull($this->query->execute(self::PARKING_ID));
    }

    private function tariff(): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->is_default = true;

        return $tariff;
    }
}
