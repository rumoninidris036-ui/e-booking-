<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badminton_fields', function (Blueprint $table): void {
            $table->string('whatsapp_number', 20)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('badminton_fields', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_number');
        });
    }
};
