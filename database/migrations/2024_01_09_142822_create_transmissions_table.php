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
        Schema::create('vehicle_transmissions', function (Blueprint $table) {
            $table->id('transmission_id');
            $table->unsignedBigInteger('vehicle_type_id');
            $table->string('name', 255);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('vehicle_type_id')->references('type_id')->on('vehicle_types'); // Add this line
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_transmissionsZ');
    }
};
