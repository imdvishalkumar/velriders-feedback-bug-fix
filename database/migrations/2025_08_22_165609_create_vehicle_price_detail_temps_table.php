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
        Schema::create('vehicle_price_detail_temps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('rental_price')->default(0);
            $table->integer('hours')->default(0);
            $table->integer('rate')->default(0)->comment('In Rupees');
            $table->float('multiplier')->default(0);
            $table->string('duration')->nullable();
            $table->decimal('per_hour_rate', 10, 2)->nullable();
            $table->string('trip_amount_km_limit')->nullable()->comment('Per KM. amount');
            $table->decimal('unlimited_km_trip_amount', 10, 2)->nullable()->comment('In Rupees');
            $table->tinyInteger('is_show')->default(1)->comment('1 = Show, 0 = Not Show');
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
        Schema::dropIfExists('vehicle_price_detail_temps');
    }
};
