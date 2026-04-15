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
        Schema::create('vehicle_model_price_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_model_id');
            $table->tinyInteger('type')->nullable()->comment('1 = Min Price Detail, 2 = Max Price Detail');
            $table->string('rental_price')->default(0);
            $table->integer('hours')->default(0);
            $table->integer('rate')->default(0)->comment('In Rupees');
            $table->float('multiplier')->default(0);
            $table->string('duration')->nullable();
            $table->decimal('per_hour_rate', 10, 2)->nullable();
            $table->string('trip_amount_km_limit')->nullable()->comment('Per KM. amount');
            $table->decimal('unlimited_km_trip_amount', 10, 2)->nullable()->comment('In Rupees');
            $table->timestamps();
            // Define foreign key constraints
            $table->foreign('vehicle_model_id')->references('model_id')->on('vehicle_models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_model_price_details');
    }
};
