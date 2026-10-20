<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\Request;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffSortEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Список тарифов: фильтры, сортировка и страница.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Parameter(
    parameter: 'TariffsIndexRequestPage',
    name: 'page',
    description: 'Номер страницы',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestPerPage',
    name: 'per_page',
    description: 'Записей на странице',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 30, maximum: 100, minimum: 1)
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
            'page' => ['sometimes', 'integer', 'gt:0'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'parking_id' => ['nullable', 'integer', 'gt:0'],
            'status' => ['nullable', 'string', Rule::enum(TariffStatusEnum::class)],
            'sort' => ['sometimes', 'string', Rule::enum(TariffSortEnum::class)],
        ];
    }
}
