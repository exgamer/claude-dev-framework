<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Cache\Billing\Tariffs\Repositories;

use App\ParkingApp\Core\Enums\CacheKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Exception;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Cache\CacheManagerInterface;

/**
 * Кеш тарифов по ID: в кеше — колонки строки, при чтении из них собирается модель.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffCacheRepository implements TariffCacheRepositoryInterface
{
    private const TTL_SECONDS = 7200;

    public function __construct(
        private readonly CacheManagerInterface $cacheManager,
        private readonly Tariff $model,
    ) {
    }

    /**
     * @throws OperationFailedAppException
     */
    public function getById(int $id): ?Tariff
    {
        // Драйверы Redis (phpredis, predis) бросают разные классы, общий у них — Exception
        try {
            $cached = $this->cacheManager->get($this->key($id));
        } catch (Exception $e) {
            throw new OperationFailedAppException('Кеш тарифов недоступен', ['tariff_id' => $id, 'error' => $e::class]);
        }

        if (! is_array($cached)) {
            return null;
        }

        /** @var Tariff */
        return $this->model->newFromBuilder($cached);
    }

    /**
     * @throws OperationFailedAppException
     */
    public function set(Tariff $tariff): void
    {
        try {
            $this->cacheManager->set($this->key($tariff->id), $tariff->getAttributes(), self::TTL_SECONDS);
        } catch (Exception $e) {
            throw new OperationFailedAppException('Кеш тарифов недоступен', ['tariff_id' => $tariff->id, 'error' => $e::class]);
        }
    }

    /**
     * @throws OperationFailedAppException
     */
    public function forget(int $id): void
    {
        try {
            $this->cacheManager->forget($this->key($id));
        } catch (Exception $e) {
            throw new OperationFailedAppException('Кеш тарифов недоступен', ['tariff_id' => $id, 'error' => $e::class]);
        }
    }

    private function key(int $id): string
    {
        return $this->cacheManager->getCacheKeyByParts(CacheKeyEnum::TARIFF->value, (string) $id);
    }
}
