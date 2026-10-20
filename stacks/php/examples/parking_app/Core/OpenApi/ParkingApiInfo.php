<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Описание API подпроекта и схемы авторизации для Swagger.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Info(version: '1.0', title: 'Parking API')]
#[OA\SecurityScheme(
    securityScheme: 'Authorization',
    type: 'http',
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\SecurityScheme(
    securityScheme: 'Signature',
    type: 'apiKey',
    description: 'HMAC-подпись машинного вызова: X-Signature: t=<unix ts>,n=<nonce>,v1=<hex HMAC-SHA256(secret, "{t}\n{n}\n{METHOD}\n{путь с query}\n{тело}")>; время — в окне от времени сервера, nonce одноразовый',
    name: 'X-Signature',
    in: 'header'
)]
final class ParkingApiInfo
{
}
