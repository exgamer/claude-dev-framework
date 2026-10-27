<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Exceptions;

use MPS\Core\Enums\AppErrorTypeEnum;
use MPS\Core\Exceptions\AppException;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Пропал или битый конфиг: ошибка сервера — 500 и error в Sentry, а не 400 и warning ядра.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class ServerConfigurationException extends InvalidConfigurationAppException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message, array $details = [])
    {
        // Конструктор InvalidConfigurationAppException жёстко ставит BAD_REQUEST — вызывается конструктор AppException
        AppException::__construct($message, AppErrorTypeEnum::INTERNAL_SERVER_ERROR, null, $details);
    }
}
