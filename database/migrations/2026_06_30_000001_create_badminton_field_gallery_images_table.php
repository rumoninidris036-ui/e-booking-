<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badminton_field_gallery_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('badminton_field_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['badminton_field_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badminton_field_gallery_images');
    }
};
