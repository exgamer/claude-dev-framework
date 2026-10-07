<?php

declare(strict_types=1);

use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;
use MPS\Utils\Http\Middleware\ApiResponseMiddleware;

Route::middleware([
    'auth:admin-api',
    'parking.permission',
    ApiResponseMiddleware::class,
])
    ->prefix('parking/admin')
    ->as('parking.admin.')
    ->whereNumber('id')
    ->group(function () {
        Route::get('tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('tariffs/{id}', [TariffController::class, 'view'])->name('tariffs.view');
        Route::post('tariffs', [TariffController::class, 'create'])->name('tariffs.create');
        Route::put('tariffs/{id}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::post('tariffs/{id}/set-default', [TariffController::class, 'setDefault'])->name('tariffs.set-default');
    });
