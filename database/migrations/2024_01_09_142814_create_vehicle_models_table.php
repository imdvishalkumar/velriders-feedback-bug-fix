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
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id('model_id');
            $table->string('name', 255);
            $table->unsignedBigInteger('manufacturer_id');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Define foreign key constraint
            $table->foreign('manufacturer_id')->references('manufacturer_id')->on('vehicle_manufacturers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
    }
};
