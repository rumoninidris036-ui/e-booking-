<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('badminton_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('booked')->index();
            $table->decimal('price_per_hour', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['badminton_field_id', 'booking_date', 'start_time', 'end_time'], 'bookings_unique_slot');
            $table->index(['badminton_field_id', 'booking_date', 'status'], 'bookings_field_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
