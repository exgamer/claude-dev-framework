<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Состояние тарифа решает модель: начальное — по дате активации, допустимость переходов — can*(); fill() состояние не пишет.
 * Tests\TestCase — каст datetime берёт формат даты у соединения.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffTest extends TestCase
{
    public function test_new_tariff_without_activation_date_is_active_and_not_default(): void
    {
        $tariff = (new Tariff())->initState(null);

        $this->assertSame(TariffStatusEnum::ACTIVE, $tariff->status);
        $this->assertFalse($tariff->is_default);
        $this->assertNull($tariff->scheduled_at);
    }

    public function test_new_tariff_with_activation_date_is_scheduled_and_not_default(): void
    {
        $scheduledAt = Carbon::parse('2026-11-01 00:00:00');
        $tariff = (new Tariff())->initState($scheduledAt);

        $this->assertSame(TariffStatusEnum::SCHEDULED, $tariff->status);
        $this->assertFalse($tariff->is_default);
        $this->assertEquals($scheduledAt, $tariff->scheduled_at);
    }

    public function test_fill_does_not_write_state(): void
    {
        $tariff = (new Tariff())->fill([
            'name' => 'Стандарт',
            'status' => TariffStatusEnum::ARCHIVED,
            'is_default' => true,
            'scheduled_at' => '2026-11-01 00:00:00',
        ]);

        $this->assertSame('Стандарт', $tariff->name);
        $this->assertSame(['name'], array_keys($tariff->getAttributes()));
    }

    public function test_transitions_allowed_by_status(): void
    {
        $cases = [
            // статус => [canActivate, canBeDefault, canArchive]
            [TariffStatusEnum::SCHEDULED, true, false, true],
            [TariffStatusEnum::ACTIVE, false, true, true],
            [TariffStatusEnum::ARCHIVED, false, false, false],
        ];

        foreach ($cases as [$status, $canActivate, $canBeDefault, $canArchive]) {
            $tariff = $this->tariff($status);

            $this->assertSame($canActivate, $tariff->canActivate(), $status->value);
            $this->assertSame($canBeDefault, $tariff->canBeDefault(), $status->value);
            $this->assertSame($canArchive, $tariff->canArchive(), $status->value);
        }
    }

    private function tariff(TariffStatusEnum $status): Tariff
    {
        $tariff = new Tariff();
        // Фикстура: состояние строки из БД
        $tariff->status = $status;

        return $tariff;
    }
}
