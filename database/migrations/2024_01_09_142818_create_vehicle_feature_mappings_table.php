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
        Schema::create('vehicle_feature_mappings', function (Blueprint $table) {
            $table->id('mapping_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('feature_id');
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('feature_id')->references('feature_id')->on('vehicle_features')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_feature_mappings');
    }
};
