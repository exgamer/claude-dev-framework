<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs\PartnerBillingTariffsNotifier;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Http\DataObjects\HttpRequest;
use MPS\Utils\Components\Http\Enums\HttpRequestTypeEnum;
use MPS\Utils\Components\Http\Interfaces\HttpClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * Отправка тарифа партнёру: тело — контракт партнёра (DTO интеграции), отказ и ошибка сети — OperationFailedAppException.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PartnerBillingTariffsNotifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TARIFF_ID = 42;

    private HttpClientInterface&MockInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Mockery::mock(HttpClientInterface::class);
        $this->client->shouldReceive('freshRequest')->andReturn(new PendingRequest());
    }

    public function test_tariff_is_sent_in_partner_contract(): void
    {
        $expectedBody = [
            'external_id' => self::TARIFF_ID,
            'parking_id' => 15,
            'name' => 'Стандарт',
            'currency' => 'KZT',
            'price' => 50000,
        ];
        $this->client
            ->shouldReceive('request')
            ->once()
            ->with(Mockery::on(fn (HttpRequest $request): bool => $request->getMethod() === HttpRequestTypeEnum::POST
                && $request->getUrl() === 'https://partner.example/tariffs'
                && $request->getData() === $expectedBody))
            ->andReturn($this->response(201));

        $this->notifier()->sendCreated(self::TARIFF_ID, $this->dto());
    }

    public function test_rejected_tariff_fails(): void
    {
        $this->client->shouldReceive('request')->once()->andReturn($this->response(422));

        $this->expectException(OperationFailedAppException::class);

        $this->notifier()->sendCreated(self::TARIFF_ID, $this->dto());
    }

    public function test_connection_error_fails(): void
    {
        $this->client->shouldReceive('request')->once()->andThrow(new ConnectionException('timeout'));

        $this->expectException(OperationFailedAppException::class);

        $this->notifier()->sendCreated(self::TARIFF_ID, $this->dto());
    }

    public function test_missing_timeout_is_configuration_error(): void
    {
        $config = new Config(['services' => ['partner_billing' => ['base_url' => 'https://partner.example']]]);

        $this->expectException(InvalidConfigurationAppException::class);

        new PartnerBillingTariffsNotifier($this->client, $config);
    }

    private function notifier(): PartnerBillingTariffsNotifier
    {
        $config = new Config(['services' => ['partner_billing' => ['base_url' => 'https://partner.example', 'timeout' => 5]]]);

        return new PartnerBillingTariffsNotifier($this->client, $config);
    }

    private function dto(): TariffDto
    {
        return TariffDto::make()
            ->setParkingId(15)
            ->setName('Стандарт')
            ->setCurrency(CurrencyEnum::KZT)
            ->setPrice(50000)
            ->setGraceMinutes(15);
    }

    private function response(int $status): Response
    {
        $psrResponse = new PsrResponse($status);

        return new Response($psrResponse);
    }
}
