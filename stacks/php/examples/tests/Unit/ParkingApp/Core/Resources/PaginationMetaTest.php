<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Resources;

use App\ParkingApp\Core\Resources\PaginationMeta;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Блок pagination совпадает с Go SDK (gosdk-db-core 1.0.10, pagination.Pages): те же ключи и значения.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PaginationMetaTest extends TestCase
{
    /**
     * @return array<string, array{int, int, int, array<string, int>}>
     */
    public static function pages(): array
    {
        return [
            'первая из двух' => [42, 30, 1, [
                'total' => 42, 'last_page' => 2, 'offset' => 0, 'from' => 0, 'to' => 0,
                'per_page' => 30, 'current_page' => 1, 'prev_page' => 1, 'next_page' => 2,
            ]],
            'последняя' => [42, 30, 2, [
                'total' => 42, 'last_page' => 2, 'offset' => 30, 'from' => 30, 'to' => 0,
                'per_page' => 30, 'current_page' => 2, 'prev_page' => 1, 'next_page' => 2,
            ]],
            'за последней' => [42, 30, 5, [
                'total' => 42, 'last_page' => 2, 'offset' => 120, 'from' => 120, 'to' => 0,
                'per_page' => 30, 'current_page' => 5, 'prev_page' => 1, 'next_page' => 2,
            ]],
            'пустой список' => [0, 30, 1, [
                'total' => 0, 'last_page' => 0, 'offset' => 0, 'from' => 0, 'to' => 0,
                'per_page' => 30, 'current_page' => 1, 'prev_page' => 0, 'next_page' => 0,
            ]],
        ];
    }

    /**
     * @param array<string, int> $expected
     */
    #[DataProvider('pages')]
    public function test_pagination_matches_go_sdk(int $total, int $perPage, int $page, array $expected): void
    {
        $paginator = new LengthAwarePaginator([], $total, $perPage, $page);

        $this->assertSame($expected, PaginationMeta::fromPaginator($paginator));
    }
}
