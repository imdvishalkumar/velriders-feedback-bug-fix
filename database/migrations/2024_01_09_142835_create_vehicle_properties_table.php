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
        Schema::create('vehicle_properties', function (Blueprint $table) {
            $table->id('property_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->integer('mileage')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->unsignedBigInteger('transmission_id')->nullable();
            $table->integer('seating_capacity')->nullable();
            $table->integer('engine_cc')->nullable();
            $table->integer('fuel_capacity')->nullable();
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('fuel_type_id')->references('fuel_type_id')->on('vehicle_fuel_types')->nullable()->onDelete('cascade');
            $table->foreign('transmission_id')->references('transmission_id')->on('vehicle_transmissions')->nullable()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_properties');
    }
};
