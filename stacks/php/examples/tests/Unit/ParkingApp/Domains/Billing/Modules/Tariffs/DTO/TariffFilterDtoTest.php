<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use PHPUnit\Framework\TestCase;

/**
 * Скоуп парковок в TariffFilterDto: без умолчания «без ограничения».
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffFilterDtoTest extends TestCase
{
    public function test_scope_not_set_throws_instead_of_unrestricted(): void
    {
        $this->expectException(InvalidConfigurationAppException::class);

        TariffFilterDto::make()->getParkingIds();
    }

    public function test_explicit_null_is_unrestricted(): void
    {
        $filter = TariffFilterDto::make()->setParkingIds(null);

        $this->assertNull($filter->getParkingIds());
    }

    public function test_explicit_list_is_returned(): void
    {
        $filter = TariffFilterDto::make()->setParkingIds([1, 2]);

        $this->assertSame([1, 2], $filter->getParkingIds());
    }

    public function test_empty_list_stays_empty(): void
    {
        $filter = TariffFilterDto::make()->setParkingIds([]);

        $this->assertSame([], $filter->getParkingIds());
    }
}
