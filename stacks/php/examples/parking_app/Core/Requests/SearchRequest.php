<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use MPS\Core\Http\Requests\SearchRequest as CoreSearchRequest;

/**
 * Запрос списка формы (1) пагинации, P-19: правила SearchRequest ядра + filterRules() наследника; эталон модуля — форма (2), IndexRequest extends Request.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class SearchRequest extends CoreSearchRequest
{
    public function rules(): array
    {
        $filterRules = $this->filterRules();

        return array_merge(parent::rules(), $filterRules);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function filterRules(): array
    {
        return [];
    }
}
