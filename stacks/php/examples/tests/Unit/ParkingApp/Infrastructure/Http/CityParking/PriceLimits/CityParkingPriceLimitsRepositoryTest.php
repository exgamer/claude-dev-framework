<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\Http\CityParking\PriceLimits;

use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Infrastructure\Http\CityParking\PriceLimits\CityParkingPriceLimitsRepository;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Http\DataObjects\HttpRequest;
use MPS\Utils\Components\Http\Interfaces\HttpClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Адаптер реестра цен: 404 — лимита нет, ответ без целого max_price, ошибка ответа и сети — OperationFailedAppException.
 *
 * @author Имя Фамилия <name@example.com>
 */
class CityParkingPriceLimitsRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const PARKING_ID = 15;

    private HttpClientInterface&MockInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(HttpClientInterface::class);
        $this->client->shouldReceive('freshRequest')->andReturn(new PendingRequest());
    }

    public function test_limit_is_read_from_response(): void
    {
        $this->client
            ->shouldReceive('request')
            ->once()
            ->with(Mockery::on(fn (HttpRequest $request): bool => $request->getUrl() === 'https://registry.example/parkings/15/price-limit'
                && $request->getData() === ['currency' => 'KZT']))
            ->andReturn($this->response(200, ['max_price' => 100000]));

        $maxPrice = $this->repository()->getMaxPrice(self::PARKING_ID, CurrencyEnum::KZT);

        $this->assertSame(100000, $maxPrice);
    }

    public function test_parking_missing_in_registry_means_no_limit(): void
    {
        $this->client->shouldReceive('request')->once()->andReturn($this->response(404, []));

        $maxPrice = $this->repository()->getMaxPrice(self::PARKING_ID, CurrencyEnum::KZT);

        $this->assertNull($maxPrice);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function bodiesWithoutIntegerLimit(): array
    {
        return [
            'нет max_price' => [[]],
            'строка' => [['max_price' => '100000']],
            'bool' => [['max_price' => true]],
            'дробное' => [['max_price' => 100000.5]],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('bodiesWithoutIntegerLimit')]
    public function test_response_without_integer_limit_fails(array $body): void
    {
        $this->client->shouldReceive('request')->once()->andReturn($this->response(200, $body));

        $this->expectException(OperationFailedAppException::class);

        $this->repository()->getMaxPrice(self::PARKING_ID, CurrencyEnum::KZT);
    }

    public function test_error_response_fails(): void
    {
        $this->client->shouldReceive('request')->once()->andReturn($this->response(500, []));

        $this->expectException(OperationFailedAppException::class);

        $this->repository()->getMaxPrice(self::PARKING_ID, CurrencyEnum::KZT);
    }

    public function test_connection_error_fails(): void
    {
        $this->client->shouldReceive('request')->once()->andThrow(new ConnectionException('timeout'));

        $this->expectException(OperationFailedAppException::class);

        $this->repository()->getMaxPrice(self::PARKING_ID, CurrencyEnum::KZT);
    }

    public function test_missing_base_url_is_configuration_error(): void
    {
        $config = new Config(['services' => ['city_parking' => ['timeout' => 3]]]);

        $this->expectException(ServerConfigurationException::class);

        new CityParkingPriceLimitsRepository($this->client, $config);
    }

    private function repository(): CityParkingPriceLimitsRepository
    {
        $config = new Config(['services' => ['city_parking' => ['base_url' => 'https://registry.example/', 'timeout' => 3]]]);

        return new CityParkingPriceLimitsRepository($this->client, $config);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function response(int $status, array $body): Response
    {
        $json = (string) json_encode($body);
        $psrResponse = new PsrResponse($status, ['Content-Type' => 'application/json'], $json);

        return new Response($psrResponse);
    }
}
