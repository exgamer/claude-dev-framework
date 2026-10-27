<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Support\Collection;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;

/**
 * Репозиторий списка с пагинацией по SearchDataObject и выборкой по набору id.
 *
 * @author Имя Фамилия <name@example.com>
 */
interface SearchRepositoryInterface extends CRUDRepositoryInterface
{
    /**
     * search() CRUDRepository ядра с номером страницы: в интерфейсе ядра параметра $page нет.
     *
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
