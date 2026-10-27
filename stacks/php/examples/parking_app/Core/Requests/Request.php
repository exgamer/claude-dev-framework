<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Requests;

use Illuminate\Routing\Route;
use MPS\Core\Http\Requests\FormRequest as CoreFormRequest;

/**
 * Общее для запросов подпроекта: параметры пути проверяются rules() как поля (id ≤ 0 — 422), верх id — предел bigint.
 *
 * @author Имя Фамилия <name@example.com>
 */
class Request extends CoreFormRequest
{
    /** Верхняя граница id — предел bigint PostgreSQL: больше — 422, а не QueryException и 500 */
    public const ID_MAX = '9223372036854775807';

    /**
     * Параметры пути ({id}) проверяются rules() как поля запроса: id = 0, -1, abc — 422, а не 404 маршрута
     * (ограничения whereNumber на маршруте нет). Параметр пути перекрывает одноимённое поле тела и query.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $route = $this->route();
        $pathParameters = $route instanceof Route ? $route->parameters() : [];

        return array_merge($this->all(), $pathParameters);
    }
}
