<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Signature;

/**
 * Подписываемая часть HTTP-запроса машинного вызова.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final readonly class SignedMessage
{
    public function __construct(
        public string $method,
        /** путь с query, как на проводе */
        public string $target,
        public string $body,
    ) {
    }
}
