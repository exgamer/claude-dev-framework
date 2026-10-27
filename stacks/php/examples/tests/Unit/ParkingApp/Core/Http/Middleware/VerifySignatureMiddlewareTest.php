<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Http\Middleware;

use App\ParkingApp\Core\Enums\RequestHeaderEnum;
use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Core\Helpers\SignatureHelper;
use App\ParkingApp\Core\Http\Middleware\VerifySignatureMiddleware;
use App\ParkingApp\Core\Http\Signature\SignatureNonceRepositoryInterface;
use App\ParkingApp\Core\Http\Signature\SignedMessage;
use App\ParkingApp\Core\Http\Signature\SignedRequest;
use Carbon\Carbon;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\UnAuthorizeAppException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Подпись машинного вызова: верная — проходит и занимает nonce, битая/просроченная/повторная — 401, нет секрета или nonce живёт меньше двух окон — ошибка конфигурации (500).
 *
 * @author Имя Фамилия <name@example.com>
 */
class VerifySignatureMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SECRET = 'test-signature-secret';

    private const NOW = 1788150000;

    private const NONCE = '11111111-2222-3333-4444-555555555555';

    private const TARGET = '/parking/integration/tariffs/42?locale=ru';

    /** @var array<string, true> */
    private array $usedNonces = [];

    private SignatureNonceRepositoryInterface&MockInterface $nonceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::createFromTimestamp(self::NOW));
        $this->nonceRepository = Mockery::mock(SignatureNonceRepositoryInterface::class);
        $this->nonceRepository
            ->shouldReceive('remember')
            ->andReturnUsing(function (string $nonce): bool {
                if (isset($this->usedNonces[$nonce])) {
                    return false;
                }

                $this->usedNonces[$nonce] = true;

                return true;
            });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_signed_request_passes_and_takes_nonce(): void
    {
        $header = $this->header(self::SECRET, self::NOW);
        $request = $this->request($header);

        $response = $this->handle($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayHasKey(self::NONCE, $this->usedNonces);
    }

    public function test_timestamp_inside_window_passes(): void
    {
        $header = $this->header(self::SECRET, self::NOW - 299);
        $request = $this->request($header);

        $this->assertSame(Response::HTTP_OK, $this->handle($request)->getStatusCode());
    }

    public function test_replayed_nonce_is_unauthorized(): void
    {
        $header = $this->header(self::SECRET, self::NOW);
        $this->handle($this->request($header));

        $this->expectException(UnAuthorizeAppException::class);

        $this->handle($this->request($header));
    }

    public function test_rejected_signature_does_not_take_nonce(): void
    {
        $cases = [
            'нет заголовка' => null,
            'чужой секрет' => $this->header('other', self::NOW),
            'другой путь' => $this->header(self::SECRET, self::NOW, '/parking/integration/tariffs/43'),
            'время вне окна' => $this->header(self::SECRET, self::NOW - 301),
            'время из будущего' => $this->header(self::SECRET, self::NOW + 301),
        ];

        foreach ($cases as $case => $header) {
            try {
                $this->handle($this->request($header));
                $this->fail('Ожидалось UnAuthorizeAppException: ' . $case);
            } catch (UnAuthorizeAppException) {
                $this->assertSame([], $this->usedNonces, $case);
            }
        }
    }

    public function test_missing_secret_is_server_configuration_error(): void
    {
        $this->expectException(ServerConfigurationException::class);

        new VerifySignatureMiddleware($this->nonceRepository, $this->config(['secret' => '']));
    }

    public function test_nonce_ttl_shorter_than_two_windows_is_server_configuration_error(): void
    {
        $this->expectException(ServerConfigurationException::class);

        new VerifySignatureMiddleware($this->nonceRepository, $this->config(['nonce_ttl_seconds' => 599]));
    }

    public function test_server_configuration_error_is_500(): void
    {
        $exception = new ServerConfigurationException('Не задан INTEGRATION_SIGNATURE_SECRET');

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getStatusCode());
        $this->assertInstanceOf(InvalidConfigurationAppException::class, $exception);
    }

    private function header(string $secret, int $timestamp, string $target = self::TARGET): string
    {
        $message = new SignedMessage('GET', $target, '');
        $signed = new SignedRequest($timestamp, self::NONCE, $message);

        return SignatureHelper::header($secret, $signed);
    }

    private function handle(Request $request): Response
    {
        $middleware = new VerifySignatureMiddleware($this->nonceRepository, $this->config([]));

        return $middleware->handle($request, static fn (): Response => new Response());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function config(array $overrides): Config
    {
        $signature = array_merge(['secret' => self::SECRET, 'tolerance_seconds' => 300, 'nonce_ttl_seconds' => 600], $overrides);

        return new Config(['parking-app' => ['integration_signature' => $signature]]);
    }

    private function request(?string $header): Request
    {
        $request = Request::create(self::TARGET);

        if ($header !== null) {
            $request->headers->set(RequestHeaderEnum::SIGNATURE->value, $header);
        }

        return $request;
    }
}
