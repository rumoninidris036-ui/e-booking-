<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('whatsapp_notified_at')->nullable()->after('invoice_generated_at');
            $table->json('whatsapp_notification_response')->nullable()->after('whatsapp_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'whatsapp_notified_at',
                'whatsapp_notification_response',
            ]);
        });
    }
};
