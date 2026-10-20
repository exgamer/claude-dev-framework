<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Helpers;

use App\ParkingApp\Core\Helpers\SignatureHelper;
use App\ParkingApp\Core\Http\Signature\SignedMessage;
use App\ParkingApp\Core\Http\Signature\SignedRequest;
use PHPUnit\Framework\TestCase;

/**
 * HMAC-подпись машинного вызова: вектор Go-эталона, отказ на битый формат и на смену секрета, метода, пути или тела.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class SignatureHelperTest extends TestCase
{
    // Вектор посчитан независимо: printf '...' | openssl dgst -sha256 -hmac vector-secret; тот же — в Go-эталоне
    private const SECRET = 'vector-secret';

    private const TIMESTAMP = 1788150000;

    private const NONCE = '11111111-2222-3333-4444-555555555555';

    private const TARGET = '/api/v1/integration/tariffs/42';

    private const BODY = '{"a":1}';

    private const VECTOR = 'aef0607db22c169c20df74d33bf170e8ff20b1cdc9238e35391e1a3dfcf9925e';

    public function test_signature_matches_independent_vector(): void
    {
        $request = new SignedRequest(self::TIMESTAMP, self::NONCE, $this->message('post'));
        $signature = SignatureHelper::sign(self::SECRET, $request);

        $this->assertSame(self::VECTOR, $signature);
    }

    public function test_header_is_parsed_back_and_verified(): void
    {
        $request = new SignedRequest(self::TIMESTAMP, self::NONCE, $this->message());
        $header = SignatureHelper::header(self::SECRET, $request);

        $parsed = SignatureHelper::parse($header);

        $this->assertNotNull($parsed);
        $this->assertSame(self::TIMESTAMP, $parsed->timestamp);
        $this->assertSame(self::NONCE, $parsed->nonce);
        $message = $this->message();
        $this->assertTrue(SignatureHelper::verify(self::SECRET, $parsed, $message));
    }

    public function test_upper_case_hex_is_accepted(): void
    {
        $parsed = SignatureHelper::parse('t=' . self::TIMESTAMP . ',n=' . self::NONCE . ',v1=' . strtoupper(self::VECTOR));

        $this->assertNotNull($parsed);
        $message = $this->message();
        $this->assertTrue(SignatureHelper::verify(self::SECRET, $parsed, $message));
    }

    public function test_malformed_headers_are_rejected(): void
    {
        $valid = 't=' . self::TIMESTAMP . ',n=' . self::NONCE . ',v1=' . self::VECTOR;
        $headers = [
            'нет заголовка' => null,
            'пустой' => '',
            'без значения' => 't=,n=' . self::NONCE . ',v1=' . self::VECTOR,
            'без =' => 't' . self::TIMESTAMP . ',n=' . self::NONCE . ',v1=' . self::VECTOR,
            'повтор ключа' => $valid . ',t=' . self::TIMESTAMP,
            'лишний ключ' => $valid . ',x=1',
            'нет подписи' => 't=' . self::TIMESTAMP . ',n=' . self::NONCE,
            'время не число' => 't=-5,n=' . self::NONCE . ',v1=' . self::VECTOR,
            'короткий nonce' => 't=' . self::TIMESTAMP . ',n=abc,v1=' . self::VECTOR,
            'подпись не hex' => 't=' . self::TIMESTAMP . ',n=' . self::NONCE . ',v1=' . str_repeat('z', 64),
        ];

        foreach ($headers as $case => $header) {
            $this->assertNull(SignatureHelper::parse($header), $case);
        }
    }

    public function test_signature_does_not_match_other_secret_method_target_or_body(): void
    {
        $parsed = SignatureHelper::parse('t=' . self::TIMESTAMP . ',n=' . self::NONCE . ',v1=' . self::VECTOR);
        $this->assertNotNull($parsed);

        $cases = [
            'чужой секрет' => ['other', $this->message()],
            'другой метод' => [self::SECRET, $this->message(method: 'GET')],
            'другой путь' => [self::SECRET, $this->message(target: '/api/v1/integration/tariffs/43')],
            'другое тело' => [self::SECRET, $this->message(body: '{"a":2}')],
        ];

        foreach ($cases as $case => [$secret, $message]) {
            $this->assertFalse(SignatureHelper::verify($secret, $parsed, $message), $case);
        }
    }

    private function message(string $method = 'POST', string $target = self::TARGET, string $body = self::BODY): SignedMessage
    {
        return new SignedMessage($method, $target, $body);
    }
}
