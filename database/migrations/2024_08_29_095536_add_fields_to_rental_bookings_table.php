<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('initial_vehicle_id')->nullable()->after('vehicle_id');
            $table->unsignedBigInteger('location_id')->nullable()->after('initial_vehicle_id');
            $table->tinyInteger('location_from')->nullable()->comment('1 = Branch, 2 = Car Host Pickup Location')->after('location_id');
            $table->tinyInteger('is_aggrement_send')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_bookings', function (Blueprint $table) {
            //
        });
    }
};
