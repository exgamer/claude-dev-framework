<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs\Mocks;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Workflows\Billing\Tariffs\TariffNotifierInterface;

/**
 * Заглушка партнёрского биллинга для тестового окружения: тариф принят.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PartnerBillingTariffsNotifierMock implements TariffNotifierInterface
{
    public function sendCreated(int $tariffId, TariffDto $dto): void
    {
    }
}
