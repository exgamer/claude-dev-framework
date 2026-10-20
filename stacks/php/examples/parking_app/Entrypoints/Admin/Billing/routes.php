<?php

declare(strict_types=1);

use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Право — на каждом маршруте: чтение и запись различаются (имена прав — как в Go-эталоне)
Route::middleware([
    'auth:admin-api',
    'resolve.admin.parkings',
    'api.response',
])
    ->prefix('parking/admin')
    ->as('parking.admin.')
    ->whereNumber('id')
    ->group(function () {
        Route::get('tariffs', [TariffController::class, 'index'])
            ->middleware('permission:parking.admin.tariffs.view')
            ->name('tariffs.index');
        Route::get('tariffs/default', [TariffController::class, 'viewDefault'])
            ->middleware('permission:parking.admin.tariffs.view')
            ->name('tariffs.view-default');
        Route::get('tariffs/{id}', [TariffController::class, 'view'])
            ->middleware('permission:parking.admin.tariffs.view')
            ->name('tariffs.view');
        Route::post('tariffs', [TariffController::class, 'create'])
            ->middleware('permission:parking.admin.tariffs.create')
            ->name('tariffs.create');
        Route::put('tariffs/{id}', [TariffController::class, 'update'])
            ->middleware('permission:parking.admin.tariffs.update')
            ->name('tariffs.update');
        Route::post('tariffs/{id}/set-default', [TariffController::class, 'setDefault'])
            ->middleware('permission:parking.admin.tariffs.update')
            ->name('tariffs.set-default');
    });
