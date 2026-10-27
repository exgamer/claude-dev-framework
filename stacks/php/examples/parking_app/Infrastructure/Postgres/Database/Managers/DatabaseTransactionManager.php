<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Database\Managers;

use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Менеджер транзакций на соединении БД по умолчанию.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class DatabaseTransactionManager implements TransactionManagerInterface
{
    /**
     * @template TReturn
     * @param callable():TReturn $callback
     * @return TReturn
     *
     * @throws Throwable
     */
    public function run(callable $callback, int $attempts = 1)
    {
        return DB::transaction($callback, $attempts);
    }
}
