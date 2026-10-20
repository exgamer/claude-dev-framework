<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Support\Collection;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;

/**
 * Репозиторий формы (1) пагинации для PaginatedQueryHelper: CRUD ядра + выборка по набору id.
 * search() объявлен заново ради $page: у CRUDRepository ядра 1.6.0 он есть, у CRUDRepositoryInterface — нет.
 * Реализация — extends CRUDRepository + allByIds(): условие whereIn — в репозитории, не в хелпере.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface SearchRepositoryInterface extends CRUDRepositoryInterface
{
    /**
     * @param array<string, mixed> $params
     *
     * @return LengthAwarePaginatorContract<int, mixed>|Collection<int, mixed>
     */
    public function search(
        array $params = [],
        int|array|string|null $id = null,
        int $perPage = 30,
        ?int $page = null,
    ): LengthAwarePaginatorContract|Collection;

    /**
     * Записи по набору первичных ключей; порядок — как вернула БД.
     *
     * @param list<int|string> $ids
     *
     * @return Collection<int, mixed>
     */
    public function allByIds(array $ids): Collection;
}
