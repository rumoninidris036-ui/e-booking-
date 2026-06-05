<?php

declare(strict_types=1);

use App\Services\Booking\FieldScheduleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badminton_fields', function (Blueprint $table): void {
            $table->time('open_time')->default(FieldScheduleService::DEFAULT_OPEN_TIME)->after('price_per_hour');
            $table->time('close_time')->default(FieldScheduleService::DEFAULT_CLOSE_TIME)->after('open_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(FieldScheduleService::DEFAULT_SLOT_DURATION_MINUTES)->after('close_time');
        });
    }

    public function down(): void
    {
        Schema::table('badminton_fields', function (Blueprint $table): void {
            $table->dropColumn([
                'open_time',
                'close_time',
                'slot_duration_minutes',
            ]);
        });
    }
};
