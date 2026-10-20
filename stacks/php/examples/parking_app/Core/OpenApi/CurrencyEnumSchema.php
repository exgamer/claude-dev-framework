<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\OpenApi;

use MPS\Core\Enums\CurrencyEnum;
use OpenApi\Attributes as OA;

/**
 * Схема валюты для Swagger: enum ядра (MPS\Core\Enums\CurrencyEnum) атрибутом не помечен — схема здесь, Request и Response ссылаются через ref.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Schema(schema: 'CurrencyEnum', description: 'Валюта', type: 'string', enum: [
    CurrencyEnum::KZT->value,
    CurrencyEnum::USD->value,
], example: 'KZT')]
final class CurrencyEnumSchema
{
}
