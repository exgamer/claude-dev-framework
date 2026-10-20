<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;
use MPS\Core\Exceptions\BadRequestAppException;

/**
 * Поиск формы (1) пагинации (P-19): search() CRUD-репозитория ядра → ['items', 'pagination', 'params'].
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class PaginatedQueryHelper
{
    private int $defaultPerPage = 30;

    private string $dataKey = 'items';

    private bool $asArray = true;

    public static function instance(CRUDRepositoryInterface $repo): self
    {
        return new self($repo);
    }

    public function __construct(
        private readonly CRUDRepositoryInterface $repo,
    ) {
    }

    public function setDefaultPerPage(int $value): self
    {
        $this->defaultPerPage = $value;

        return $this;
    }

    public function setDataKey(string $value): self
    {
        $this->dataKey = $value;

        return $this;
    }

    public function asArray(bool $value = true): self
    {
        $this->asArray = $value;

        return $this;
    }

    /**
     * С id — один элемент; expanded строкой через запятую — массивом.
     * search() ядра принимает perPage только int, «без пагинации» в нём не выражается: весь набор по id — searchByIds().
     *
     * @return array<int|string, mixed>
     *
     * @throws BadRequestAppException perPage не целое ≥ 1 (в том числе false)
     */
    public function search(SearchDataObject $dto): array
    {
        $id = $dto->getId();
        $params = $dto->getParams();
        $perPage = $dto->getPerPage() ?? $this->defaultPerPage;

        if (! is_int($perPage) || $perPage < 1) {
            throw new BadRequestAppException('perPage — целое не меньше 1; весь набор по id — searchByIds()');
        }

        if (! empty($params['expanded']) && is_string($params['expanded'])) {
            $expanded = explode(',', $params['expanded']);
            $params['expanded'] = array_map('trim', $expanded);
        }

        $searchResult = $this->repo->search(
            params: $params,
            id: $id,
            perPage: $perPage,
        );

        if ($searchResult instanceof LengthAwarePaginator) {
            return $this->fromPaginator($searchResult, $id, $params);
        }

        $items = $this->asArray ? $searchResult->toArray() : $searchResult->all();

        if ($id !== null) {
            return array_shift($items) ?? [];
        }

        return [
            $this->dataKey => $items,
            'params' => $params,
        ];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function searchById(int|string $id): array
    {
        $result = $this->searchByIds([$id]);

        return array_shift($result) ?? [];
    }

    /**
     * Список без keyBy (у searchByIds() ядра он по умолчанию): карту по ключу собирает потребитель, не хелпер запросов.
     *
     * @param array<int, int|string> $ids
     *
     * @return list<mixed>
     */
    public function searchByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $primaryKey = $this->repo->getPrimaryKeyName();

        $models = $this->repo->allByCondition(
            static fn (Builder $query) => $query->whereIn($primaryKey, $ids),
        );
        $result = $this->asArray ? $models->toArray() : $models->all();

        return array_values($result);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<int|string, mixed>
     */
    private function fromPaginator(LengthAwarePaginator $paginator, mixed $id, array $params): array
    {
        $collection = $paginator->getCollection();
        $items = $this->asArray ? $collection->toArray() : $collection->all();

        if ($id !== null) {
            return array_shift($items) ?? [];
        }

        $pagination = $paginator->toArray();
        unset($pagination['data']);

        return [
            $this->dataKey => $items,
            'pagination' => $pagination,
            'params' => $params,
        ];
    }
}
