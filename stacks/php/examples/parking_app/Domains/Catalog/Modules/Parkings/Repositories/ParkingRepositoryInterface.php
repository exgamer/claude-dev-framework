<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories;

use App\ParkingApp\Domains\Catalog\Modules\Parkings\DTO\ParkingMaxStayDto;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;

/**
 * Парковки домена Catalog для workflow Billing: существование (oneById ядра) и наибольшая длительность стоянки.
 *
 * @author Имя Фамилия <name@example.com>
 */
interface ParkingRepositoryInterface extends CRUDRepositoryInterface
{
    /**
     * Наибольшая длительность стоянки парковки; null — без ограничения или парковки нет (существование — oneById).
     */
    public function getMaxStayById(int $id): ?ParkingMaxStayDto;
}
