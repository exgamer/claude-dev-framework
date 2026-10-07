<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators\TariffDtoValidator;
use PHPUnit\Framework\TestCase;

class TariffDtoValidatorTest extends TestCase
{
    public function testValidDtoHasNoErrors(): void
    {
        $dto = TariffDto::make()
            ->setParkingId(1)
            ->setName('Стандарт')
            ->setGraceMinutes(15);

        $this->assertSame([], TariffDtoValidator::validate($dto));
    }

    public function testBlankNameAndTooLongGraceAreRejected(): void
    {
        $dto = TariffDto::make()
            ->setParkingId(1)
            ->setName('  ')
            ->setGraceMinutes(2000);

        $errors = TariffDtoValidator::validate($dto);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('grace_minutes', $errors);
    }
}
