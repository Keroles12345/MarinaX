<?php

use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/documentation', static fn () => response()->file(public_path('api-documentation.html')))
    ->name('api.documentation');
Route::get('/openapi.yaml', static fn () => response()->file(public_path('openapi.yaml'), [
    'Content-Type' => 'application/yaml; charset=UTF-8',
]))
    ->name('api.openapi');

Route::prefix('v1')->name('api.v1.')->middleware(['tenant.api-key', 'throttle:60,1'])->group(function (): void {
    Route::get('/trips', [WebsiteController::class, 'trips'])->name('trips.index');
    Route::get('/trips/{trip}', [WebsiteController::class, 'trip'])->name('trips.show');
    Route::get('/companies', [WebsiteController::class, 'companies'])->name('companies.index');
    Route::get('/currencies', [WebsiteController::class, 'currencies'])->name('currencies.index');
    Route::post('/bookings', [WebsiteController::class, 'storeBooking'])
        ->middleware('throttle:10,1')
        ->name('bookings.store');
});
