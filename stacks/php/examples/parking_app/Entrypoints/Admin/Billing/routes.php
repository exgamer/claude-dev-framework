<?php

declare(strict_types=1);

use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Controllers\TariffController;
use Illuminate\Support\Facades\Route;

// Право — на каждом маршруте: чтение и запись различаются.
// {id} без ограничения маршрута (whereNumber и т.п.): любой сегмент доходит до Request — неверный id 422, а не 404.
// Статические пути (tariffs/default) — до tariffs/{id}, иначе {id} их перехватит.
Route::middleware([
    'api.response',
    'auth:admin-api',
    'resolve.admin.parkings',
])
    ->prefix('parking/admin')
    ->as('parking.admin.')
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
