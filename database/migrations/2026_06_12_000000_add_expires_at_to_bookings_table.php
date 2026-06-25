<?php

declare(strict_types=1);

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('status')->index();
        });

        Booking::query()
            ->where('status', Booking::STATUS_PENDING)
            ->whereNull('expires_at')
            ->chunkById(100, function ($bookings): void {
                foreach ($bookings as $booking) {
                    $booking->forceFill([
                        'expires_at' => CarbonImmutable::parse($booking->created_at)->addMinutes(Booking::PENDING_PAYMENT_TIMEOUT_MINUTES),
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
