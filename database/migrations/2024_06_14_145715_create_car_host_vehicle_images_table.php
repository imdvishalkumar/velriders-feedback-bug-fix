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
        Schema::create('car_host_vehicle_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicles_id')->nullable();
            $table->unsignedBigInteger('car_host_pickup_locations_id')->nullable();
            $table->tinyInteger('image_type')->default(NULL)->comment('1 = Parking Spot, 2 = Intirior, 3 = Exterior');
            $table->string('vehicle_img');
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('vehicles_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('car_host_pickup_locations_id')->references('id')->on('car_host_pickup_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_host_vehicle_images');
    }
};
