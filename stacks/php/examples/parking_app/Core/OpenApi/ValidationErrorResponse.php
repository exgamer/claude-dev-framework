<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 422 для Swagger: формат ошибки ApiResponseMiddleware, ошибки по полям — в details.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Response(
    response: 'ValidationError422',
    description: 'Ошибка валидации',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/ValidationErrorData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ValidationErrorData',
    required: ['status', 'error', 'message', 'details'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 422),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'VALIDATION_ERROR'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'VALIDATION ERROR'),
        new OA\Property(
            property: 'details',
            description: 'Поле → сообщения об ошибке',
            type: 'object',
            example: ['price' => ['Цена выше лимита городского реестра для этой парковки: 100000']],
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string'))
        ),
    ],
    type: 'object'
)]
final class ValidationErrorResponse
{
}
