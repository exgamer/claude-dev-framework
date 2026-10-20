<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Signature;

/**
 * Использованные nonce подписей машинного вызова.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface SignatureNonceRepositoryInterface
{
    /**
     * Занимает nonce на время жизни из конфига атомарно; false — nonce уже встречался (повтор запроса).
     * Сбой хранилища — исключение драйвера: проверить повтор нельзя, запрос не пропускается.
     */
    public function remember(string $nonce): bool;
}
