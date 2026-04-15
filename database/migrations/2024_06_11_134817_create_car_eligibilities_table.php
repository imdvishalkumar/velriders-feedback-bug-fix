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
        Schema::create('car_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('car_hosts_id');
            $table->unsignedBigInteger('car_host_pickup_location_id')->nullable();
            $table->string('km_driven', 20)->nullable();
            $table->tinyInteger('fast_tag')->default(0)->comment('0 = Fastag not exist, 1 = Fastag exist');
            $table->tinyInteger('night_time')->default(0)->comment('0 = Not Restricted, 1 = Restricted');
            $table->timestamps();

            //Define Foraign Keys
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('car_hosts_id')->references('id')->on('car_hosts')->onDelete('cascade');
            //$table->foreign('car_host_pickup_location_id')->references('id')->on('car_host_pickup_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_eligibilities');
    }
};
