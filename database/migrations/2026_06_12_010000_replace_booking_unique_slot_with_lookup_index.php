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
            $table->dropUnique('bookings_unique_slot');
            $table->index([
                'badminton_field_id',
                'booking_date',
                'start_time',
                'end_time',
            ], 'bookings_slot_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_slot_lookup_index');
            $table->unique([
                'badminton_field_id',
                'booking_date',
                'start_time',
                'end_time',
            ], 'bookings_unique_slot');
        });
    }
};
