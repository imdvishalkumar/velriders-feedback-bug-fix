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
        Schema::create('rental_bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('vehicle_id');
            //$table->unsignedBigInteger('from_branch_id');
            //$table->unsignedBigInteger('to_branch_id');
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->integer('rental_duration_minutes');
            $table->boolean('unlimited_kms')->default(false);
            $table->decimal('total_cost', 10, 2);
            $table->string('status', 255)->nullable();
            $table->string('rental_type', 10);
            $table->json('penalty_details')->nullable();
           // $table->json('calculation_details')->nullable();
            $table->string('start_otp', 10)->nullable();
            $table->string('end_otp', 10)->nullable();
            $table->string('start_kilometers')->nullable();
            $table->string('end_kilometers')->nullable();
            $table->json('data_json')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->unsignedBigInteger('sequence_no')->nullable();
            $table->decimal('tax_rate', 10, 2)->default(0)->nullable();
            $table->timestamps();
        
            // Define foreign key constraints
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            //$table->foreign('from_branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            //$table->foreign('to_branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_bookings');
    }
};
