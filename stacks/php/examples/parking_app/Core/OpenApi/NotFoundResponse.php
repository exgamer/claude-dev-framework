<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 404 для Swagger: формат ошибки ApiResponseMiddleware; только у эндпойнтов, ищущих запись по ID.
 *
 * @author Имя Фамилия <name@example.com>
 */
#[OA\Response(
    response: 'NotFound404',
    description: 'Запись не найдена',
    content: new OA\JsonContent(ref: '#/components/schemas/NotFoundResponse')
)]
#[OA\Schema(
    schema: 'NotFoundResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/NotFoundData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'NotFoundData',
    required: ['status', 'error', 'message'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 404),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'NOT_FOUND'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'Тариф не найден'),
    ],
    type: 'object'
)]
final class NotFoundResponse
{
}
