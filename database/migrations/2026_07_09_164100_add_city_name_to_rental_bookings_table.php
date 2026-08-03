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
        Schema::table('rental_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_bookings', 'city_name')) {
                $table->string('city_name')->nullable()->after('rental_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_bookings', function (Blueprint $table) {
            $table->dropColumn('city_name');
        });
    }
};
