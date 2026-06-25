<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Booking\BookingService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:expire-pending', function (): int {
    $expiredCount = app(BookingService::class)->expireOverduePendingBookings();

    $this->comment(sprintf('Expired %d pending booking(s).', $expiredCount));

    return self::SUCCESS;
})->purpose('Auto-cancel pending bookings that exceeded the payment window.');
