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
            $table->timestamp('rating_whatsapp_notified_at')->nullable()->after('finished_at');
            $table->json('rating_whatsapp_notification_response')->nullable()->after('rating_whatsapp_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['rating_whatsapp_notified_at', 'rating_whatsapp_notification_response']);
        });
    }
};
