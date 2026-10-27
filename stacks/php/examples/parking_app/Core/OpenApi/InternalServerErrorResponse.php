<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 500 для Swagger: формат ошибки ApiResponseMiddleware без деталей реализации.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Response(
    response: 'ServerError500',
    description: 'Внутренняя ошибка сервера',
    content: new OA\JsonContent(ref: '#/components/schemas/InternalServerErrorResponse')
)]
#[OA\Schema(
    schema: 'InternalServerErrorResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/InternalServerErrorData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'InternalServerErrorData',
    required: ['status', 'error', 'message'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 500),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'INTERNAL_SERVER_ERROR'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'Внутренняя ошибка сервера'),
    ],
    type: 'object'
)]
final class InternalServerErrorResponse
{
}
