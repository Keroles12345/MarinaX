<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/documentation', static fn () => response()->file(public_path('api-documentation.html')))
    ->name('api.documentation');
Route::get('/openapi.yaml', static fn () => response()->file(public_path('openapi.yaml'), [
    'Content-Type' => 'application/yaml; charset=UTF-8',
]))
    ->name('api.openapi');
Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('api.webhooks.payments');

Route::prefix('v1')->name('api.v1.')->middleware(['tenant.api-key', 'throttle:60,1'])->group(function (): void {
    Route::get('/trips', [WebsiteController::class, 'trips'])->middleware('tenant.api-scope:trips:read')->name('trips.index');
    Route::get('/trips/{trip}', [WebsiteController::class, 'trip'])->middleware('tenant.api-scope:trips:read')->name('trips.show');
    Route::get('/companies', [WebsiteController::class, 'companies'])->middleware('tenant.api-scope:companies:read')->name('companies.index');
    Route::get('/currencies', [WebsiteController::class, 'currencies'])->middleware('tenant.api-scope:currencies:read')->name('currencies.index');
    Route::post('/bookings', [WebsiteController::class, 'storeBooking'])
        ->middleware('tenant.api-scope:bookings:create')
        ->middleware('throttle:10,1')
        ->name('bookings.store');
    Route::get('/bookings/{booking}', [WebsiteController::class, 'booking'])
        ->middleware('tenant.api-scope:bookings:read')
        ->name('bookings.show');
    Route::patch('/bookings/{booking}/status', [WebsiteController::class, 'updateBookingStatus'])
        ->middleware('tenant.api-scope:bookings:manage')
        ->name('bookings.status');
    Route::post('/bookings/{booking}/payments', [WebsiteController::class, 'createPayment'])
        ->middleware('tenant.api-scope:payments:create')
        ->name('bookings.payments');
    Route::post('/payments/{payment}/refund', [WebsiteController::class, 'refundPayment'])
        ->middleware('tenant.api-scope:payments:refund')
        ->name('payments.refund');
});
