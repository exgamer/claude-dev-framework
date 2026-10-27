<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\DataObjects\DataObject;

/**
 * Тариф по умолчанию парковки и статус доставки последнего события о нём.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ParkingDefaultTariffDto extends DataObject
{
    private Tariff $tariff;

    /** null — событий о тарифе не было */
    private ?OutboxTariffEventStatusEnum $lastEventStatus = null;

    public function getTariff(): Tariff
    {
        return $this->tariff;
    }

    public function setTariff(Tariff $value): self
    {
        $this->tariff = $value;

        return $this;
    }

    public function getLastEventStatus(): ?OutboxTariffEventStatusEnum
    {
        return $this->lastEventStatus;
    }

    public function setLastEventStatus(?OutboxTariffEventStatusEnum $value): self
    {
        $this->lastEventStatus = $value;

        return $this;
    }
}
