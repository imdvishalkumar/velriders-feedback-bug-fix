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
        Schema::create('car_host_vehicle_start_journey_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_host_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->tinyInteger('image_type')->default(NULL)->comment('1 = Intirior, 2 = Exterior');
            $table->string('vehicle_img');
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('car_host_id')->references('id')->on('car_hosts')->onDelete('cascade');
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_host_vehicle_start_journey_images');
    }
};
