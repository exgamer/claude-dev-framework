<?php

declare(strict_types=1);

namespace Tests\Feature\ParkingApp\Admin\Billing\Fixtures;

use App\ParkingApp\Core\Enums\RequestAttributeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Хост-middleware Admin в тесте маршрутов: вместо permission:{право} и resolve.admin.parkings —
 * пропускает запрос как super-admin (парковки без ограничения).
 *
 * @author Имя Фамилия <name@example.com>
 */
final class SuperAdminMiddleware
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $request->attributes->set(RequestAttributeEnum::PARKING_IDS->value, null);

        return $next($request);
    }
}
