<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 403 для Swagger: формат ошибки ApiResponseMiddleware; нет права (permission: на маршруте) или parking_id недоступной парковки (resolve.admin.parkings).
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Response(
    response: 'AccessDenied403',
    description: 'Нет права или парковка недоступна',
    content: new OA\JsonContent(ref: '#/components/schemas/AccessDeniedResponse')
)]
#[OA\Schema(
    schema: 'AccessDeniedResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/AccessDeniedData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AccessDeniedData',
    required: ['status', 'error', 'message'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 403),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'ACCESS_DENIED'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'Парковка недоступна'),
    ],
    type: 'object'
)]
final class AccessDeniedResponse
{
}
