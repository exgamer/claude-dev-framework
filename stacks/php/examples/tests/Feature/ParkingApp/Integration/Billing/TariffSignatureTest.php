<?php

declare(strict_types=1);

namespace Tests\Feature\ParkingApp\Integration\Billing;

use App\ParkingApp\Core\Enums\RequestHeaderEnum;
use App\ParkingApp\Core\Helpers\SignatureHelper;
use App\ParkingApp\Core\Http\Signature\SignedMessage;
use App\ParkingApp\Core\Http\Signature\SignedRequest;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * GET /parking/integration/tariffs/{id}: верная HMAC-подпись — тариф без скоупа, иначе — 401 без вызова сервиса;
 * неверный id пути (0, -1, abc) при верной подписи — 422 из Request, а не 404 маршрута.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffSignatureTest extends TestCase
{
    private const SECRET = 'test-signature-secret';

    private const TARGET = '/parking/integration/tariffs/42';

    private TariffCrudServiceInterface&MockInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('parking-app.integration_signature', [
            'secret' => self::SECRET,
            'tolerance_seconds' => 300,
            'nonce_ttl_seconds' => 600,
        ]);
        // nonce — в кеше приложения: array-хранилище вместо Redis, чистое на каждый тест
        config()->set('cache.default', 'array');

        $this->service = $this->mock(TariffCrudServiceInterface::class);
    }

    public function test_signed_request_gets_tariff_without_parking_scope(): void
    {
        $this->service->shouldReceive('findById')->once()->with(42, null)->andReturn($this->tariff());

        $this->getSigned(self::TARGET, (string) Str::uuid())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 42)
            ->assertJsonPath('data.price', 50000)
            ->assertJsonMissingPath('data.name');
    }

    public function test_request_without_signature_is_unauthorized(): void
    {
        $this->service->shouldNotReceive('findById');

        $this->getJson(self::TARGET)->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_signature_of_other_tariff_is_unauthorized(): void
    {
        $this->service->shouldNotReceive('findById');
        $nonce = (string) Str::uuid();
        $header = $this->header('/parking/integration/tariffs/43', $nonce);

        $this->getJson(self::TARGET, [RequestHeaderEnum::SIGNATURE->value => $header])->assertUnauthorized();
    }

    public function test_replayed_request_is_unauthorized(): void
    {
        $this->service->shouldReceive('findById')->once()->andReturn($this->tariff());
        $nonce = (string) Str::uuid();

        $this->getSigned(self::TARGET, $nonce)->assertOk();
        $this->getSigned(self::TARGET, $nonce)->assertUnauthorized();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return [
            'ноль' => ['0'],
            'отрицательный' => ['-1'],
            'не число' => ['abc'],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_signed_request_with_invalid_id_is_validation_error(string $id): void
    {
        $this->service->shouldNotReceive('findById');

        $this->getSigned('/parking/integration/tariffs/' . $id, (string) Str::uuid())
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('id', 'data.details');
    }

    private function getSigned(string $target, string $nonce): TestResponse
    {
        $header = $this->header($target, $nonce);

        // getJson() шлёт в GET тело «[]», а подписано пустое — как у реального клиента
        return $this->withHeaders([RequestHeaderEnum::SIGNATURE->value => $header])->get($target, ['Accept' => 'application/json']);
    }

    private function header(string $target, string $nonce): string
    {
        $message = new SignedMessage('GET', $target, '');
        $now = Carbon::now()->getTimestamp();
        $request = new SignedRequest($now, $nonce, $message);

        return SignatureHelper::header(self::SECRET, $request);
    }

    private function tariff(): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = 42;
        $tariff->parking_id = 15;
        $tariff->name = 'Стандарт';
        $tariff->currency = CurrencyEnum::KZT;
        $tariff->price = 50000;
        $tariff->is_default = true;
        $tariff->status = TariffStatusEnum::ACTIVE;
        $tariff->grace_minutes = 15;

        return $tariff;
    }
}
