<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('rental_bookings') && !Schema::hasColumn('rental_bookings', 'otp_verified_at')) {
            Schema::table('rental_bookings', function (Blueprint $table) {
                $table->dateTime('otp_verified_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rental_bookings') && Schema::hasColumn('rental_bookings', 'otp_verified_at')) {
            Schema::table('rental_bookings', function (Blueprint $table) {
                $table->dropColumn('otp_verified_at');
            });
        }
    }
};
