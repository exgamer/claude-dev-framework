<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Core\Requests\SearchRequest;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'TariffsIndexRequestParkingId',
    name: 'parking_id',
    description: 'ID парковки',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', nullable: true)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestStatus',
    name: 'status',
    description: 'Статус: active, archived',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', enum: ['active', 'archived'], nullable: true)
)]
#[OA\Parameter(
    parameter: 'TariffsIndexRequestSort',
    name: 'sort',
    description: 'Сортировка через запятую, минус = DESC. Поля: name, created_at',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', example: '-created_at', nullable: true)
)]
class IndexRequest extends SearchRequest
{
    protected array $sortAttributes = ['name', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function filterRules(): array
    {
        return [
            'parking_id' => ['nullable', 'integer', 'gt:0'],
            'status' => ['nullable', 'string', Rule::enum(TariffStatusEnum::class)],
        ];
    }
}
