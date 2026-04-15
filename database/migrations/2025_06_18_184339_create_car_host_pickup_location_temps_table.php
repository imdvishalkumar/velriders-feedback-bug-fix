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
        Schema::create('car_host_pickup_location_temps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_host_pickup_locations_id');
            $table->unsignedBigInteger('car_hosts_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('name')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('location', 500);
            $table->tinyInteger('parking_type_id')->nullable();
            $table->tinyInteger('is_primary')->default(0)->comment('1 = Primary, 2 = Not Primary');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('car_hosts_id')->references('id')->on('car_hosts')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_host_pickup_location_temps');
    }
};
