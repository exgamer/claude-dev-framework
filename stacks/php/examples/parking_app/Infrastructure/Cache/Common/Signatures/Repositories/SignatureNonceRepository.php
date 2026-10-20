<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Cache\Common\Signatures\Repositories;

use App\ParkingApp\Core\Enums\CacheKeyEnum;
use App\ParkingApp\Core\Http\Signature\SignatureNonceRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Использованные nonce подписей машинного вызова в кеше приложения (Redis).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class SignatureNonceRepository implements SignatureNonceRepositoryInterface
{
    private readonly int $ttlSeconds;

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(
        private readonly Cache $cache,
        Config $config,
    ) {
        $ttlSeconds = $config->get('parking-app.integration_signature.nonce_ttl_seconds');

        if (! is_numeric($ttlSeconds) || (int) $ttlSeconds <= 0) {
            throw new InvalidConfigurationAppException('Не задан INTEGRATION_SIGNATURE_NONCE_TTL_SECONDS');
        }

        $this->ttlSeconds = (int) $ttlSeconds;
    }

    public function remember(string $nonce): bool
    {
        // add — атомарная запись «если ключа нет» (в Redis — SET NX): из двух одинаковых запросов пройдёт один.
        // В CacheManagerInterface mps/utils такой операции нет — поэтому кеш Laravel
        return $this->cache->add(CacheKeyEnum::SIGNATURE_NONCE->value . ':' . $nonce, 1, $this->ttlSeconds);
    }
}
