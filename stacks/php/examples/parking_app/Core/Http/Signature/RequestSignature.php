<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Signature;

/**
 * Разобранный заголовок подписи машинного вызова.
 *
 * @author Имя Фамилия <name@example.com>
 */
final readonly class RequestSignature
{
    public function __construct(
        public int $timestamp,
        public string $nonce,
        /** hex в нижнем регистре */
        public string $signature,
    ) {
    }
}
