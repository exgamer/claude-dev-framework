<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\CityParking\PriceLimits;

use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use App\ParkingApp\Infrastructure\Http\CityParking\DTO\PriceLimitResponseDto;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Http\Enums\HttpRequestTypeEnum;
use MPS\Utils\Components\Http\Factories\HttpClientFactory;
use MPS\Utils\Components\Http\Interfaces\HttpClientInterface;

/**
 * Адаптер лимитов цены городского реестра парковок (CityParking).
 *
 * @author Имя Фамилия <name@example.com>
 */
class CityParkingPriceLimitsRepository implements PriceLimitRepositoryInterface
{
    private readonly string $baseUrl;

    private readonly int $timeoutSeconds;

    /**
     * @throws ServerConfigurationException
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        Config $config,
    ) {
        $baseUrl = $config->get('services.city_parking.base_url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new ServerConfigurationException('Не задан CITY_PARKING_BASE_URL');
        }

        $timeoutSeconds = $config->get('services.city_parking.timeout');

        if (! is_numeric($timeoutSeconds) || (int) $timeoutSeconds <= 0) {
            throw new ServerConfigurationException('Не задан CITY_PARKING_TIMEOUT');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = (int) $timeoutSeconds;
    }

    /**
     * @throws OperationFailedAppException
     */
    public function getMaxPrice(int $parkingId, CurrencyEnum $currency): ?int
    {
        $request = HttpClientFactory::makeRequest(
            HttpRequestTypeEnum::GET,
            $this->baseUrl . '/parkings/' . $parkingId . '/price-limit',
            ['currency' => $currency->value],
        );
        // Таймаут — на запрос: HttpClient применяет HttpClientConfig один раз на процесс (static в applyConfig)
        $pendingRequest = $this->client->freshRequest()->timeout($this->timeoutSeconds);
        $request->setRequestInstance($pendingRequest);

        try {
            $response = $this->client->request($request);
        } catch (ConnectionException) {
            throw new OperationFailedAppException('Реестр парковок недоступен', ['parking_id' => $parkingId]);
        }

        if (! $response instanceof Response) {
            throw new OperationFailedAppException('Реестр парковок не ответил', ['parking_id' => $parkingId]);
        }

        // 404 — парковки нет в реестре, лимит не задан
        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new OperationFailedAppException('Реестр парковок ответил ошибкой', [
                'parking_id' => $parkingId,
                'status' => $response->status(),
            ]);
        }

        $limit = $this->toPriceLimit($response, $parkingId);

        return $limit->getMaxPrice();
    }

    /**
     * Ответ → DTO интеграции: тип проверяется строго до сеттера (fromArray ядра привёл бы "100" и true к int).
     *
     * @throws OperationFailedAppException
     */
    private function toPriceLimit(Response $response, int $parkingId): PriceLimitResponseDto
    {
        $maxPrice = $response->json('max_price');

        if (! is_int($maxPrice)) {
            throw new OperationFailedAppException('Реестр парковок вернул лимит без max_price', ['parking_id' => $parkingId]);
        }

        return PriceLimitResponseDto::make()->setMaxPrice($maxPrice);
    }
}
