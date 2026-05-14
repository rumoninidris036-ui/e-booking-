<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badminton_field_facility', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('badminton_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['badminton_field_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badminton_field_facility');
    }
};
