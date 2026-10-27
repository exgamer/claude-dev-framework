<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 503 для Swagger: формат ошибки ApiResponseMiddleware; у эндпойнтов, где OperationFailedAppException доходит до ответа.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Response(
    response: 'ServiceUnavailable503',
    description: 'Внешняя система или зависимость недоступна',
    content: new OA\JsonContent(ref: '#/components/schemas/ServiceUnavailableResponse')
)]
#[OA\Schema(
    schema: 'ServiceUnavailableResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceUnavailableData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ServiceUnavailableData',
    required: ['status', 'error', 'message'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 503),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'OPERATION_FAILED'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'Реестр парковок недоступен'),
    ],
    type: 'object'
)]
final class ServiceUnavailableResponse
{
}
