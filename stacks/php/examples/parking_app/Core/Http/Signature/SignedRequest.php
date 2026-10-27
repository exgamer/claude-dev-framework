<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Signature;

/**
 * Сообщение с меткой времени и nonce отправителя — то, что подписывается.
 *
 * @author Имя Фамилия <name@example.com>
 */
final readonly class SignedRequest
{
    public function __construct(
        public int $timestamp,
        public string $nonce,
        public SignedMessage $message,
    ) {
    }
}
