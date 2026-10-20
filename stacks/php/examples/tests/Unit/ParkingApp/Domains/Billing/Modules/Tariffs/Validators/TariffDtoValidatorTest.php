<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Domains\Billing\Modules\Tariffs\Validators;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators\TariffDtoValidator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Инварианты TariffDtoValidator.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffDtoValidatorTest extends TestCase
{
    public function test_valid_dto_has_no_errors(): void
    {
        $dto = TariffDto::make()
            ->setParkingId(1)
            ->setName('Стандарт')
            ->setGraceMinutes(15);

        $this->assertSame([], TariffDtoValidator::validate($dto));
    }

    public function test_blank_name_and_grace_over_a_day_are_rejected(): void
    {
        $dto = TariffDto::make()
            ->setParkingId(1)
            ->setName('  ')
            ->setGraceMinutes(2000);

        $errors = TariffDtoValidator::validate($dto);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('grace_minutes', $errors);
    }

    public function test_activation_date_in_past_is_rejected_and_in_future_is_accepted(): void
    {
        Carbon::setTestNow('2026-10-20 12:00:00');

        try {
            $past = TariffDto::make()
                ->setParkingId(1)
                ->setName('Стандарт')
                ->setScheduledAt(Carbon::parse('2026-10-20 11:59:59'));
            $future = TariffDto::make()
                ->setParkingId(1)
                ->setName('Стандарт')
                ->setScheduledAt(Carbon::parse('2026-10-21 00:00:00'));

            $this->assertArrayHasKey('scheduled_at', TariffDtoValidator::validate($past));
            $this->assertSame([], TariffDtoValidator::validate($future));
        } finally {
            Carbon::setTestNow();
        }
    }
}
