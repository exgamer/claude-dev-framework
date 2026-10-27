<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ответ списка формы (1) пагинации: результат PaginatedQueryHelper как есть — items + pagination (схема PaginationMeta), как у Go SDK.
 *
 * @author Имя Фамилия <name@example.com>
 */
class PaginateResource extends JsonResource
{
}
