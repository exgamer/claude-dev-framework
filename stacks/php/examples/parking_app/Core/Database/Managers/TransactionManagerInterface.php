<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Managers;

use Throwable;

/**
 * Менеджер транзакций подпроекта: в mps/core он есть только с 1.10.
 *
 * @author Имя Фамилия <name@example.com>
 */
interface TransactionManagerInterface
{
    /**
     * @template TReturn
     * @param callable():TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public function run(callable $callback, int $attempts = 1);
}
