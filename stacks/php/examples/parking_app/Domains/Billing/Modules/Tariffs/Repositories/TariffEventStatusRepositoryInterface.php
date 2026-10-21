<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories;

use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;

/**
 * Статус доставки событий о тарифе — то, что модулю Tariffs нужно от соседнего модуля OutboxTariffEvents (O-6):
 * интерфейс объявляет потребитель, реализацию соседа связывает DI (`Infrastructure/Postgres/Billing/Providers/definitions.php`).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface TariffEventStatusRepositoryInterface
{
    /**
     * Статус последнего события тарифа по ID; событий нет — null.
     */
    public function getLatestStatusByTariffId(int $tariffId): ?OutboxTariffEventStatusEnum;
}
