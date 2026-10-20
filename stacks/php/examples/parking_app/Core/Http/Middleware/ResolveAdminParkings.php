<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Middleware;

use App\ParkingApp\Core\Enums\RequestAttributeEnum;
use Closure;
use Illuminate\Http\Request;
use MPS\Core\Exceptions\AccessDeniedAppException;
use MPS\Core\Exceptions\UnAuthorizeAppException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Парковки администратора в контекст запроса (алиас resolve.admin.parkings, после auth:admin-api и parking.permission).
 * super-admin — null (без ограничения); parking_id из query или тела, которого нет среди доступных, — 403.
 * Сервис и репозиторий получают список фильтром и про администратора не знают.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class ResolveAdminParkings
{
    /**
     * @param Closure(Request): Response $next
     *
     * @throws UnAuthorizeAppException
     * @throws AccessDeniedAppException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin-api');

        if (! $admin) {
            throw new UnAuthorizeAppException('Требуется авторизация');
        }

        $parkingIds = $admin->isSuperAdmin() ? null : array_values(array_map(intval(...), $admin->getParkingIds()));

        $requestedParkingId = filter_var($request->input('parking_id'), FILTER_VALIDATE_INT);

        if ($parkingIds !== null && $requestedParkingId !== false && ! in_array($requestedParkingId, $parkingIds, true)) {
            throw new AccessDeniedAppException('Нет доступа к парковке');
        }

        $request->attributes->set(RequestAttributeEnum::PARKING_IDS->value, $parkingIds);

        return $next($request);
    }
}
