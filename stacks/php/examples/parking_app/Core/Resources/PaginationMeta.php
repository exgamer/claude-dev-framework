<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use OpenApi\Attributes as OA;

/**
 * Блок pagination ответа списка — ключи и значения как у Go SDK (gosdk-db-core, pagination.Pagination): клиент читает списки обоих стеков одинаково.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['total', 'last_page', 'offset', 'from', 'to', 'per_page', 'current_page', 'prev_page', 'next_page'],
    properties: [
        new OA\Property(property: 'total', description: 'Всего записей', type: 'integer', example: 42),
        new OA\Property(property: 'last_page', description: 'Всего страниц; 0 — записей нет', type: 'integer', example: 2),
        new OA\Property(property: 'offset', description: 'Пропущено записей до страницы', type: 'integer', example: 0),
        new OA\Property(property: 'from', description: 'Как в Go SDK: равно offset; 0 — записей нет', type: 'integer', example: 0),
        new OA\Property(property: 'to', description: 'Как в Go SDK: не заполняется, всегда 0', type: 'integer', example: 0),
        new OA\Property(property: 'per_page', description: 'Записей на странице', type: 'integer', example: 30),
        new OA\Property(property: 'current_page', description: 'Текущая страница', type: 'integer', example: 1),
        new OA\Property(property: 'prev_page', description: 'Предыдущая страница; на первой — 1, записей нет — 0', type: 'integer', example: 1),
        new OA\Property(property: 'next_page', description: 'Следующая страница, не больше last_page', type: 'integer', example: 2),
    ],
    type: 'object'
)]
final class PaginationMeta
{
    /**
     * Значения — по алгоритму pagination.Pages Go SDK, чтобы оба стека отдавали одно и то же.
     *
     * @param LengthAwarePaginator<int, mixed> $paginator
     *
     * @return array<string, int>
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): array
    {
        $total = $paginator->total();
        $perPage = $paginator->perPage();
        $page = $paginator->currentPage();
        // lastPage() Laravel не меньше 1, у Go SDK при пустом списке — 0
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $prevPage = $page > 1 ? $page - 1 : $page;
        $nextPage = $page + 1;

        if ($page > $lastPage) {
            $nextPage = $lastPage;
            $prevPage = $lastPage - 1;
        }

        $nextPage = min($nextPage, $lastPage);
        $from = $offset;

        if ($lastPage === 0) {
            $from = 0;
            $prevPage = 0;
        }

        return [
            'total' => $total,
            'last_page' => $lastPage,
            'offset' => $offset,
            'from' => $from,
            'to' => 0,
            'per_page' => $perPage,
            'current_page' => $page,
            'prev_page' => $prevPage,
            'next_page' => $nextPage,
        ];
    }
}
