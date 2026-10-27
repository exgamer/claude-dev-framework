<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Core\Requests\Request;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffSortEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Список тарифов: фильтры, сортировка и страница.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Parameter(
    parameter: 'TariffsIndexRequestPage',
    name: 'page',
    description: 'Номер страницы; 0 — первая',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, maximum: Page::MAX_PAGE, minimum: 0)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestPerPage',
    name: 'per_page',
    description: 'Записей на странице',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: Page::DEFAULT_PER_PAGE, maximum: Page::MAX_PER_PAGE, minimum: 1)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestParkingId',
    name: 'parking_id',
    description: 'ID парковки',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestStatus',
    name: 'status',
    description: 'Статус',
    in: 'query',
    required: false,
    schema: new OA\Schema(ref: '#/components/schemas/TariffStatusEnum')
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestSort',
    name: 'sort',
    description: 'Сортировка, минус — по убыванию. По умолчанию -created_at',
    in: 'query',
    required: false,
    schema: new OA\Schema(ref: '#/components/schemas/TariffSortEnum')
)]
class IndexRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:0', 'max:' . Page::MAX_PAGE],
            'per_page' => ['sometimes', 'integer', 'between:1,' . Page::MAX_PER_PAGE],
            'parking_id' => ['nullable', 'integer', 'gt:0', 'max:' . self::ID_MAX],
            'status' => ['nullable', 'string', Rule::enum(TariffStatusEnum::class)],
            'sort' => ['sometimes', 'string', Rule::enum(TariffSortEnum::class)],
        ];
    }
}
