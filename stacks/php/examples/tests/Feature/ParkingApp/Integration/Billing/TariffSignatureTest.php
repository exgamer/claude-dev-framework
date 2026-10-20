<?php

declare(strict_types=1);

namespace Tests\Feature\ParkingApp\Integration\Billing;

use App\ParkingApp\Core\Enums\RequestHeaderEnum;
use App\ParkingApp\Core\Helpers\SignatureHelper;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use Tests\TestCase;

/**
 * GET /parking/integration/tariffs/{id} — только с HMAC-подписью: верная — тариф без скоупа парковок,
 * без заголовка, с подписью другого пути или повтором nonce — 401 и сервис не вызывается.
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
        $header = SignatureHelper::header(self::SECRET, Carbon::now()->getTimestamp(), (string) Str::uuid(), 'GET', '/parking/integration/tariffs/43', '');

        $this->getJson(self::TARGET, [RequestHeaderEnum::SIGNATURE->value => $header])->assertUnauthorized();
    }

    public function test_replayed_request_is_unauthorized(): void
    {
        $this->service->shouldReceive('findById')->once()->andReturn($this->tariff());
        $nonce = (string) Str::uuid();

        $this->getSigned(self::TARGET, $nonce)->assertOk();
        $this->getSigned(self::TARGET, $nonce)->assertUnauthorized();
    }

    private function getSigned(string $target, string $nonce): TestResponse
    {
        $header = SignatureHelper::header(self::SECRET, Carbon::now()->getTimestamp(), $nonce, 'GET', $target, '');

        return $this->getJson($target, [RequestHeaderEnum::SIGNATURE->value => $header]);
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
