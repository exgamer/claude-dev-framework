<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Http\PartnerBilling\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Infrastructure\Http\PartnerBilling\DTO\PartnerTariffRequestDto;
use App\ParkingApp\Workflows\Billing\Tariffs\TariffNotifierInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Http\Enums\HttpRequestTypeEnum;
use MPS\Utils\Components\Http\Factories\HttpClientFactory;
use MPS\Utils\Components\Http\Interfaces\HttpClientInterface;

/**
 * Адаптер отправки тарифов в партнёрскую биллинговую систему (PartnerBilling).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class PartnerBillingTariffsNotifier implements TariffNotifierInterface
{
    private readonly string $baseUrl;

    private readonly int $timeoutSeconds;

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        Config $config,
    ) {
        $baseUrl = $config->get('services.partner_billing.base_url');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new InvalidConfigurationAppException('Не задан PARTNER_BILLING_BASE_URL');
        }

        $timeoutSeconds = $config->get('services.partner_billing.timeout');

        if (! is_numeric($timeoutSeconds) || (int) $timeoutSeconds <= 0) {
            throw new InvalidConfigurationAppException('Не задан PARTNER_BILLING_TIMEOUT');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSeconds = (int) $timeoutSeconds;
    }

    /**
     * @throws OperationFailedAppException
     */
    public function sendCreated(int $tariffId, TariffDto $dto): void
    {
        $partnerTariff = PartnerTariffRequestDto::make()
            ->setExternalId($tariffId)
            ->setParkingId($dto->getParkingId())
            ->setName($dto->getName())
            ->setCurrency($dto->getCurrency())
            ->setPrice($dto->getPrice());
        // Ключи тела — поля DTO в snake_case, enum — значением (ArrayableTrait ядра)
        $body = $partnerTariff->toArrayWithSnakeKeys();
        $request = HttpClientFactory::makeRequest(HttpRequestTypeEnum::POST, $this->baseUrl . '/tariffs', $body);
        // Таймаут — на запрос: HttpClient применяет HttpClientConfig один раз на процесс (static в applyConfig)
        $pendingRequest = $this->client->freshRequest()->timeout($this->timeoutSeconds);
        $request->setRequestInstance($pendingRequest);

        try {
            $response = $this->client->request($request);
        } catch (ConnectionException) {
            throw new OperationFailedAppException('Партнёрский биллинг недоступен', ['tariff_id' => $tariffId]);
        }

        if (! $response instanceof Response) {
            throw new OperationFailedAppException('Партнёрский биллинг не ответил', ['tariff_id' => $tariffId]);
        }

        if (! $response->successful()) {
            throw new OperationFailedAppException('Партнёрский биллинг не принял тариф', [
                'tariff_id' => $tariffId,
                'status' => $response->status(),
            ]);
        }
    }
}
