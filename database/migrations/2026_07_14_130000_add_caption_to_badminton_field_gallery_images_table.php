<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badminton_field_gallery_images', function (Blueprint $table): void {
            $table->string('caption')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('badminton_field_gallery_images', function (Blueprint $table): void {
            $table->dropColumn('caption');
        });
    }
};
