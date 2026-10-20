<?php

declare(strict_types=1);

namespace App\ParkingApp\Workflows\Billing\Tariffs\DTO;

use MPS\Core\DataObjects\DataObjectCollection;

/**
 * Коллекция строк списка для партнёра в порядке выдачи репозитория.
 *
 * @extends DataObjectCollection<ParkingTariffDto>
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class ParkingTariffItemsDto extends DataObjectCollection
{
    protected function hydrate(array $item): ParkingTariffDto
    {
        return ParkingTariffDto::make()->fromArray($item);
    }
}
