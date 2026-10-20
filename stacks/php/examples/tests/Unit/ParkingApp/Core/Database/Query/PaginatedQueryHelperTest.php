<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Database\Query;

use App\ParkingApp\Core\Database\Query\PaginatedQueryHelper;
use App\ParkingApp\Core\Database\Query\SearchRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;
use MPS\Core\Exceptions\BadRequestAppException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Форма (1) пагинации: perPage не целое ≥ 1 — 400 без запроса, page доходит до search(), ключи ответа — items/pagination, набор по id — allByIds().
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PaginatedQueryHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private SearchRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(SearchRepositoryInterface::class);
    }

    /**
     * @return array<string, array{int|bool}>
     */
    public static function invalidPerPage(): array
    {
        return [
            'ноль' => [0],
            'отрицательное' => [-1],
            'false — «без пагинации»' => [false],
        ];
    }

    #[DataProvider('invalidPerPage')]
    public function test_per_page_below_one_is_bad_request_without_query(int|bool $perPage): void
    {
        $this->repository->shouldNotReceive('search');
        $search = SearchDataObject::make()->setPerPage($perPage);

        $this->expectException(BadRequestAppException::class);

        PaginatedQueryHelper::instance($this->repository)->search($search);
    }

    public function test_page_is_passed_to_search_and_result_has_fixed_keys(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 31]], 31, 30, 2);
        $this->repository
            ->shouldReceive('search')
            ->once()
            ->with([], null, 30, 2)
            ->andReturn($paginator);
        $search = SearchDataObject::make()->setPerPage(30)->setPage(2);

        $result = PaginatedQueryHelper::instance($this->repository)->search($search);

        $this->assertSame(['items', 'pagination'], array_keys($result));
        $this->assertSame([['id' => 31]], $result['items']);
        $this->assertSame(2, $result['pagination']['current_page']);
    }

    public function test_search_by_ids_uses_repository_method(): void
    {
        $this->repository->shouldReceive('allByIds')->once()->with([3, 1])->andReturn(collect([['id' => 1], ['id' => 3]]));

        $result = PaginatedQueryHelper::instance($this->repository)->searchByIds([3, 1]);

        $this->assertSame([['id' => 1], ['id' => 3]], $result);
    }

    public function test_search_by_empty_ids_does_not_query(): void
    {
        $this->repository->shouldNotReceive('allByIds');

        $result = PaginatedQueryHelper::instance($this->repository)->searchByIds([]);

        $this->assertSame([], $result);
    }
}
