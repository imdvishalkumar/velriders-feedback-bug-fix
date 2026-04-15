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
        Schema::create('vehicle_pricing_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('duration');
            $table->decimal('per_hour_rate', 10, 2);
            $table->decimal('trip_amount', 10, 2)->comment('In Rupees');
            $table->string('trip_amount_km_limit')->nullable();
            $table->decimal('unlimited_km_trip_amount', 10, 2)->comment('In Rupees');
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_pricing_controls');
    }
};
