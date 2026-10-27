<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;

/**
 * Статус доставки событий о тарифе.
 *
 * @author Имя Фамилия <name@example.com>
 */
interface TariffEventStatusRepositoryInterface
{
    /**
     * Статус последнего события тарифа по ID; событий нет — null.
     */
    public function getLatestStatusByTariffId(int $tariffId): ?OutboxTariffEventStatusEnum;
}
