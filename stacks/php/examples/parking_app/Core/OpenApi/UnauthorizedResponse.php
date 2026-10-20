<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Ответ 401 для Swagger: формат ошибки ApiResponseMiddleware; нет администратора или подпись машинного вызова не прошла — причина клиенту не раскрывается.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Response(
    response: 'Unauthorized401',
    description: 'Не аутентифицирован или подпись машинного вызова недействительна',
    content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
)]
#[OA\Schema(
    schema: 'UnauthorizedResponse',
    required: ['success', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'data', ref: '#/components/schemas/UnauthorizedData'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UnauthorizedData',
    required: ['status', 'error', 'message'],
    properties: [
        new OA\Property(property: 'status', description: 'HTTP-код', type: 'integer', example: 401),
        new OA\Property(property: 'error', description: 'Тип ошибки', type: 'string', example: 'UNAUTHORIZED'),
        new OA\Property(property: 'app_error_code', description: 'Внутренний код приложения', type: 'integer', example: 0),
        new OA\Property(property: 'message', description: 'Текст ошибки', type: 'string', example: 'Не аутентифицирован'),
    ],
    type: 'object'
)]
final class UnauthorizedResponse
{
}
