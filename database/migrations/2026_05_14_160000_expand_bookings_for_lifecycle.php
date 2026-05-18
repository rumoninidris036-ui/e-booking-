<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('booking_code')->nullable()->after('id');
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->timestamp('paid_at')->nullable()->after('cancelled_at');
            $table->timestamp('finished_at')->nullable()->after('paid_at');
            $table->unique('booking_code');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropUnique(['booking_code']);
            $table->dropColumn([
                'booking_code',
                'cancellation_reason',
                'cancelled_at',
                'paid_at',
                'finished_at',
            ]);
        });
    }
};
