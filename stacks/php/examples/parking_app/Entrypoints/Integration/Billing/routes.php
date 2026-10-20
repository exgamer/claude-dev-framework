<?php

declare(strict_types=1);

use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Controllers\ParkingTariffController;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Машинный вызов партнёра: подпись вместо администратора, скоупа парковок нет.
// {id} без ограничения маршрута: неверный id — 422 из Request, а не 404.
Route::middleware([
    'api.response',
    'verify.signature',
])
    ->prefix('parking/integration')
    ->as('parking.integration.')
    ->group(function () {
        Route::get('tariffs/{id}', [TariffController::class, 'view'])->name('tariffs.view');
        Route::get('parkings/{id}/tariffs', [ParkingTariffController::class, 'index'])->name('parking-tariffs.index');
    });
