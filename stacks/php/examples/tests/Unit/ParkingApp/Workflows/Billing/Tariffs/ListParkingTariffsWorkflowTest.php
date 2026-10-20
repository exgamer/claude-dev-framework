<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use App\ParkingApp\Workflows\Billing\Tariffs\DTO\ParkingTariffDto;
use App\ParkingApp\Workflows\Billing\Tariffs\ListParkingTariffsWorkflow;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Models\Model;
use Tests\TestCase;

/**
 * Тарифы парковки с данными парковки: строки тарифов → ParkingTariffItemsDto, нет парковки — 404 без чтения тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ListParkingTariffsWorkflowTest extends TestCase
{
    private const PARKING_ID = 15;

    private ParkingRepositoryInterface&MockInterface $parkingRepository;

    private TariffRepositoryInterface&MockInterface $tariffRepository;

    private ListParkingTariffsWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parkingRepository = Mockery::mock(ParkingRepositoryInterface::class);
        $this->tariffRepository = Mockery::mock(TariffRepositoryInterface::class);
        $this->workflow = new ListParkingTariffsWorkflow($this->parkingRepository, $this->tariffRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_tariffs_of_parking_are_returned_with_parking_data(): void
    {
        $parking = new Model();
        $parking->name = 'Центральная';

        $this->parkingRepository->shouldReceive('oneById')->once()->with(self::PARKING_ID)->andReturn($parking);
        $this->tariffRepository
            ->shouldReceive('allActiveByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn(new Collection([
                $this->tariff(1, 'Стандарт', 50000, graceMinutes: 15, isDefault: true),
                $this->tariff(2, 'Ночной', 20000, graceMinutes: null, isDefault: false),
            ]));

        $result = $this->workflow->execute(self::PARKING_ID);

        $this->assertSame(self::PARKING_ID, $result->getParkingId());
        $this->assertSame('Центральная', $result->getParkingName());

        /** @var list<ParkingTariffDto> $tariffs */
        $tariffs = $result->getTariffs()->getItems();
        $this->assertCount(2, $tariffs);
        $this->assertSame(1, $tariffs[0]->getId());
        $this->assertTrue($tariffs[0]->isDefault());
        $this->assertSame('Стандарт', $tariffs[0]->getName());
        $this->assertSame(CurrencyEnum::KZT, $tariffs[0]->getCurrency());
        $this->assertSame(50000, $tariffs[0]->getPrice());
        $this->assertSame(15, $tariffs[0]->getGraceMinutes());
        $this->assertNull($tariffs[1]->getGraceMinutes());
        $this->assertSame(2, $tariffs[1]->getId());
        $this->assertFalse($tariffs[1]->isDefault());
    }

    public function test_missing_parking_is_not_found_without_reading_tariffs(): void
    {
        $this->parkingRepository->shouldReceive('oneById')->once()->with(self::PARKING_ID)->andReturn(null);
        $this->tariffRepository->shouldNotReceive('allActiveByParkingId');

        $this->expectException(NotFoundAppException::class);

        $this->workflow->execute(self::PARKING_ID);
    }

    private function tariff(int $id, string $name, int $price, ?int $graceMinutes, bool $isDefault): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = $id;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->name = $name;
        $tariff->currency = CurrencyEnum::KZT;
        $tariff->price = $price;
        $tariff->grace_minutes = $graceMinutes;
        $tariff->is_default = $isDefault;

        return $tariff;
    }
}
