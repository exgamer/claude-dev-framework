<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Источник лимитов цены парковок (внешний реестр).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface PriceLimitRepositoryInterface
{
    /**
     * @return ?int предельная цена часа в минимальных единицах валюты; null — реестр лимит для парковки не задаёт
     *
     * @throws OperationFailedAppException реестр недоступен или ответил ошибкой
     */
    public function getMaxPrice(int $parkingId, CurrencyEnum $currency): ?int;
}
