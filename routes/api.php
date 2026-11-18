<?php

use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['tenant.api-key', 'throttle:60,1'])->group(function (): void {
    Route::get('/trips', [WebsiteController::class, 'trips']);
    Route::get('/trips/{trip}', [WebsiteController::class, 'trip']);
    Route::get('/companies', [WebsiteController::class, 'companies']);
    Route::get('/currencies', [WebsiteController::class, 'currencies']);
    Route::post('/bookings', [WebsiteController::class, 'storeBooking'])->middleware('throttle:10,1');
});
