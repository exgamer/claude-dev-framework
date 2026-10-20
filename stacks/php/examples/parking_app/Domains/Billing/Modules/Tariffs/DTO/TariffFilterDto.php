<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffSortEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use MPS\Core\DataObjects\DataObject;

/**
 * Фильтр списка тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffFilterDto extends DataObject
{
    private ?int $parkingId = null;

    /** @var ?list<int> Парковки, в пределах которых список; null — без ограничения. Выставляет контроллер из контекста запроса */
    private ?array $parkingIds = null;

    private ?TariffStatusEnum $status = null;

    private TariffSortEnum $sort = TariffSortEnum::CREATED_AT_DESC;

    public function getParkingId(): ?int
    {
        return $this->parkingId;
    }

    public function setParkingId(?int $value): self
    {
        $this->parkingId = $value;

        return $this;
    }

    /**
     * @return ?list<int>
     */
    public function getParkingIds(): ?array
    {
        return $this->parkingIds;
    }

    /**
     * @param ?list<int> $value
     */
    public function setParkingIds(?array $value): self
    {
        $this->parkingIds = $value;

        return $this;
    }

    public function getStatus(): ?TariffStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?TariffStatusEnum $value): self
    {
        $this->status = $value;

        return $this;
    }

    public function getSort(): TariffSortEnum
    {
        return $this->sort;
    }

    public function setSort(TariffSortEnum $value): self
    {
        $this->sort = $value;

        return $this;
    }
}
