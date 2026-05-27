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
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_contact')->nullable()->after('customer_name');
            $table->string('customer_email')->nullable()->after('customer_contact');
            $table->string('guest_access_token', 80)->nullable()->unique()->after('customer_email');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_name',
                'customer_contact',
                'customer_email',
                'guest_access_token',
            ]);
        });
    }
};
