<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Helpers;

use App\ParkingApp\Core\Http\Signature\RequestSignature;
use App\ParkingApp\Core\Http\Signature\SignedMessage;
use App\ParkingApp\Core\Http\Signature\SignedRequest;

/**
 * HMAC-подпись машинного вызова: заголовок t=<unix ts>,n=<nonce>,v1=<hex HMAC-SHA256(secret, "{t}\n{n}\n{METHOD}\n{path?query}\n{body}")>.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class SignatureHelper
{
    private const VERSION = 'v1';

    private const NONCE_PATTERN = '/^[A-Za-z0-9\-]{8,64}$/';

    private const SIGNATURE_PATTERN = '/^[0-9a-fA-F]{64}$/';

    public static function sign(#[\SensitiveParameter] string $secret, SignedRequest $request): string
    {
        $message = $request->message;
        $canonical = $request->timestamp . "\n" . $request->nonce . "\n" . strtoupper($message->method) . "\n"
            . $message->target . "\n" . $message->body;

        return hash_hmac('sha256', $canonical, $secret);
    }

    /**
     * Значение заголовка для исходящего запроса и тестов.
     */
    public static function header(#[\SensitiveParameter] string $secret, SignedRequest $request): string
    {
        $signature = self::sign($secret, $request);

        return 't=' . $request->timestamp . ',n=' . $request->nonce . ',' . self::VERSION . '=' . $signature;
    }

    /**
     * null — заголовка нет, повтор или лишний ключ, пустое значение, чужой алфавит поля.
     */
    public static function parse(?string $header): ?RequestSignature
    {
        if ($header === null || $header === '') {
            return null;
        }

        $parts = [];

        foreach (explode(',', $header) as $pair) {
            if (! str_contains($pair, '=')) {
                return null;
            }

            [$key, $value] = explode('=', trim($pair), 2);

            if ($value === '' || isset($parts[$key]) || ! in_array($key, ['t', 'n', self::VERSION], true)) {
                return null;
            }

            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $nonce = $parts['n'] ?? '';
        $signature = $parts[self::VERSION] ?? '';

        if (! ctype_digit($timestamp) || preg_match(self::NONCE_PATTERN, $nonce) !== 1 || preg_match(self::SIGNATURE_PATTERN, $signature) !== 1) {
            return null;
        }

        return new RequestSignature((int) $timestamp, $nonce, strtolower($signature));
    }

    /**
     * Подпись совпадает с ожидаемой; сравнение — за постоянное время. Окно времени проверяет вызывающий.
     */
    public static function verify(
        #[\SensitiveParameter] string $secret,
        RequestSignature $signature,
        SignedMessage $message,
    ): bool {
        $request = new SignedRequest($signature->timestamp, $signature->nonce, $message);
        $expected = self::sign($secret, $request);

        return hash_equals($expected, $signature->signature);
    }
}
