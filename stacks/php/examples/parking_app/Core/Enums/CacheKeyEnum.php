<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Префиксы ключей кеша подпроекта: Redis общий, префикс подпроекта обязателен.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum CacheKeyEnum: string implements Enumerable
{
    use EnumerableTrait;

    /** Тариф по ID, без скоупа парковок */
    case TARIFF = 'parking:billing:tariff';

    /** Использованный nonce подписи машинного вызова */
    case SIGNATURE_NONCE = 'parking:signature:nonce';
}
