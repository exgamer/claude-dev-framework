<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO;

use MPS\Core\DataObjects\DataObject;

/**
 * Итог прогона повтора зависших событий.
 *
 * @author Имя Фамилия <name@example.com>
 */
class OutboxTariffEventRetryResultDto extends DataObject
{
    private int $published = 0;

    private int $failed = 0;

    public function getPublished(): int
    {
        return $this->published;
    }

    public function setPublished(int $value): self
    {
        $this->published = $value;

        return $this;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function setFailed(int $value): self
    {
        $this->failed = $value;

        return $this;
    }
}
