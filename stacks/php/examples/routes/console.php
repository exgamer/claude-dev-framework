<?php

declare(strict_types=1);

// Фрагмент routes/console.php хоста: расписание консольных команд

use Illuminate\Support\Facades\Schedule;

// Повтор зависших событий о тарифах (outbox): на нескольких инстансах — один прогон за раз
Schedule::command('billing:outbox-tariff-events:retry')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Страховка отложенной активации тарифов: Job потерян или не поставлен — тариф активирует этот прогон
Schedule::command('billing:tariffs:activate-due')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
