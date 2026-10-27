<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Requests;

use App\ParkingApp\Core\Database\Query\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use MPS\Core\Http\Requests\SearchRequest as CoreSearchRequest;

/**
 * Запрос списка формы (1) пагинации: правила SearchRequest ядра + filterRules() наследника.
 *
 * @author Имя Фамилия <name@example.com>
 */
class SearchRequest extends CoreSearchRequest
{
    /**
     * sort — правило ядра (allowedSortAttributes()); page и per_page — пределы подпроекта явно, не ядра
     * (у ядра page до 100000, per_page до 1000): per_page ≤ Page::MAX_PER_PAGE, page ≤ Page::MAX_PAGE, 0 — первая страница (Paginator Laravel).
     */
    public function rules(): array
    {
        $paginationRules = [
            'page' => ['nullable', 'integer', 'min:0', 'max:' . Page::MAX_PAGE],
            'per_page' => ['nullable', 'integer', 'between:1,' . Page::MAX_PER_PAGE],
        ];
        $filterRules = $this->filterRules();

        return array_merge(parent::rules(), $paginationRules, $filterRules);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function filterRules(): array
    {
        return [];
    }
}
