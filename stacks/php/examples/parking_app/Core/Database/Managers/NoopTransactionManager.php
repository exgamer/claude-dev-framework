<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Managers;

/**
 * Менеджер транзакций без БД для тестов: callback выполняется сразу.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class NoopTransactionManager implements TransactionManagerInterface
{
    /**
     * @template TReturn
     * @param callable():TReturn $callback
     * @return TReturn
     */
    public function run(callable $callback, int $attempts = 1)
    {
        return $callback();
    }
}
