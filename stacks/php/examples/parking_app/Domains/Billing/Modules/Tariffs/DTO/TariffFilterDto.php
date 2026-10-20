<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffSortEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use MPS\Core\DataObjects\DataObject;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Фильтр списка тарифов.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffFilterDto extends DataObject
{
    private ?int $parkingId = null;

    /**
     * @var ?list<int>
     */
    private ?array $parkingIds = null;

    /**
     * Скоуп выставлен явно: null в $parkingIds без вызова setParkingIds — не «без ограничения» (scope-param-defaults-unrestricted).
     */
    private bool $parkingIdsSet = false;

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
     * Парковки, доступные актору; null — без ограничения (super-admin), выставляется явно.
     * Скоуп не выставлен — исключение: запрос собран без контекста middleware, а не «все парковки».
     *
     * @return ?list<int>
     *
     * @throws InvalidConfigurationAppException
     */
    public function getParkingIds(): ?array
    {
        if (! $this->parkingIdsSet) {
            throw new InvalidConfigurationAppException('Скоуп парковок в TariffFilterDto не выставлен: нужен setParkingIds()');
        }

        return $this->parkingIds;
    }

    /**
     * @param ?list<int> $value
     */
    public function setParkingIds(?array $value): self
    {
        $this->parkingIds = $value;
        $this->parkingIdsSet = true;

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
