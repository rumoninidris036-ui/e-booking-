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
            $table->string('invoice_number')->nullable()->unique()->after('status');
            $table->string('invoice_pdf_path')->nullable()->after('invoice_number');
            $table->timestamp('invoice_generated_at')->nullable()->after('invoice_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'invoice_pdf_path',
                'invoice_generated_at',
            ]);
        });
    }
};
