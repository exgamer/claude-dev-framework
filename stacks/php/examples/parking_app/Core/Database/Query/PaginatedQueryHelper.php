<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use App\ParkingApp\Core\Resources\PaginationMeta;
use Illuminate\Pagination\LengthAwarePaginator;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;
use MPS\Core\Exceptions\BadRequestAppException;

/**
 * Поиск формы (1) пагинации: search() CRUD-репозитория ядра → ['items', 'pagination'] — формат ответа списка Go SDK.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class PaginatedQueryHelper
{
    private int $defaultPerPage = Page::DEFAULT_PER_PAGE;

    private bool $asArray = true;

    public static function instance(SearchRepositoryInterface $repo): self
    {
        return new self($repo);
    }

    public function __construct(
        private readonly SearchRepositoryInterface $repo,
    ) {
    }

    public function setDefaultPerPage(int $value): self
    {
        $this->defaultPerPage = $value;

        return $this;
    }

    public function asArray(bool $value = true): self
    {
        $this->asArray = $value;

        return $this;
    }

    /**
     * С id — один элемент. Ключи ответа фиксированы — items и pagination; expanded не разбирается —
     * параметра нет: состав ответа фиксирован эндпойнтом. Номер страницы — из SearchDataObject::getPage(),
     * null — ?page= текущего HTTP-запроса (Paginator ядра). search() ядра принимает perPage только int,
     * «без пагинации» в нём не выражается: весь набор по id — searchByIds().
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

        $searchResult = $this->repo->search(
            params: $params,
            id: $id,
            perPage: $perPage,
            page: $dto->getPage(),
        );

        if ($searchResult instanceof LengthAwarePaginator) {
            return $this->fromPaginator($searchResult, $id);
        }

        $items = $this->asArray ? $searchResult->toArray() : $searchResult->all();

        if ($id !== null) {
            return array_shift($items) ?? [];
        }

        return [
            'items' => $items,
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
     * Записи по набору id списком, без ключей по id.
     *
     * @param list<int|string> $ids
     *
     * @return list<mixed>
     */
    public function searchByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $models = $this->repo->allByIds($ids);
        $result = $this->asArray ? $models->toArray() : $models->all();

        return array_values($result);
    }

    /**
     * @param LengthAwarePaginator<int, mixed> $paginator
     *
     * @return array<int|string, mixed>
     */
    private function fromPaginator(LengthAwarePaginator $paginator, mixed $id): array
    {
        $collection = $paginator->getCollection();
        $items = $this->asArray ? $collection->toArray() : $collection->all();

        if ($id !== null) {
            return array_shift($items) ?? [];
        }

        return [
            'items' => $items,
            'pagination' => PaginationMeta::fromPaginator($paginator),
        ];
    }
}
