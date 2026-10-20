<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

/**
 * Заголовки запроса, которые читает middleware подпроекта.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
enum RequestHeaderEnum: string implements Enumerable
{
    use EnumerableTrait;

    /** HMAC-подпись машинного вызова: t=<unix ts>,n=<nonce>,v1=<hex HMAC-SHA256> */
    case SIGNATURE = 'X-Signature';
}
