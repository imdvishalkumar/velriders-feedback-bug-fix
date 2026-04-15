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
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('model_image')->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('min_km_limit', 10, 2)->nullable();
            $table->decimal('max_km_limit', 10, 2)->nullable();
            $table->decimal('min_deposit_amount', 10, 2)->nullable();
            $table->decimal('max_deposit_amount', 10, 2)->nullable();
            // Define foreign key constraint        
            $table->foreign('category_id')->references('category_id')->on('vehicle_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            //
        });
    }
};
