<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPage\BadmintonFieldController as PublicBadmintonFieldController;
use App\Http\Controllers\PublicPage\BookingController as PublicBookingController;
use App\Http\Controllers\PublicPage\FieldBookingPageController as PublicFieldBookingPageController;
use App\Http\Controllers\PublicPage\FieldScheduleController as PublicFieldScheduleController;
use App\Http\Controllers\PublicPage\GuestRatingController as PublicGuestRatingController;
use App\Http\Controllers\PublicPage\PaymentController as PublicPaymentController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use App\Services\Recommendations\FieldRecommendationCriteria;
use App\Services\Recommendations\FieldRecommendationService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $recommendationService = app(FieldRecommendationService::class);

    return view('welcome', [
        'recommendedCourts' => $recommendationService->recommend(
            FieldRecommendationCriteria::fromArray(['limit' => 3], 3)
        ),
    ]);
});

Route::get('/fields', [PublicBadmintonFieldController::class, 'index'])->name('public.fields.index');
Route::get('/fields/markers', [PublicBadmintonFieldController::class, 'markers'])->name('public.fields.markers');
Route::get('/fields/recommendations', [PublicBadmintonFieldController::class, 'recommendations'])->name('public.fields.recommendations');
Route::get('/fields/suggestions', [PublicBadmintonFieldController::class, 'suggestions'])->name('public.fields.suggestions');
Route::get('/fields/{slug}/booking', [PublicFieldBookingPageController::class, 'show'])->name('public.fields.booking');
Route::get('/fields/{slug}/schedule', [PublicFieldScheduleController::class, 'show'])->name('public.fields.schedule');
Route::get('/fields/{slug}', [PublicBadmintonFieldController::class, 'show'])->name('public.fields.show');
Route::post('/fields/{slug}/bookings', [PublicBookingController::class, 'store'])->name('public.fields.bookings.store');
Route::get('/guest-rating/{booking}', [PublicGuestRatingController::class, 'create'])
    ->middleware('signed')
    ->name('public.rating.create');
Route::post('/guest-rating/{booking}', [PublicGuestRatingController::class, 'store'])
    ->middleware('signed')
    ->name('public.rating.store');
Route::post('/bookings/{booking}/payments', [PublicPaymentController::class, 'store'])
    ->middleware('throttle:payment-create')
    ->name('payments.store');
Route::get('/payments/{payment}', [PublicPaymentController::class, 'show'])->name('payments.show');
Route::get('/payments/{payment}/return', [PublicPaymentController::class, 'handleReturn'])->name('payments.return');
Route::get('/payments/{payment}/invoice', [PublicPaymentController::class, 'downloadInvoice'])->name('payments.invoice.download');
Route::post('/webhooks/midtrans', [MidtransWebhookController::class, 'handle'])
    ->middleware('throttle:midtrans-webhook')
    ->name('webhooks.midtrans.handle');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [PublicBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [PublicBookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/cancel', [PublicBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
