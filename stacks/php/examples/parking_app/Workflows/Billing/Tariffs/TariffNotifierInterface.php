<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Уведомление внешней биллинговой системы о тарифах.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffNotifierInterface
{
    /**
     * @throws OperationFailedAppException партнёр недоступен или не принял тариф
     */
    public function sendCreated(int $tariffId, TariffDto $dto): void;
}
