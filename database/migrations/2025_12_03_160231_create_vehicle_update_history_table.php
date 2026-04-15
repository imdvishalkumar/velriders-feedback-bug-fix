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
        Schema::create('vehicle_update_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('old_vehicle_id')->nullable();
            $table->unsignedBigInteger('new_vehicle_id');
            $table->text('change_reason')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Admin user ID who made the change');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
            $table->foreign('old_vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('set null');
            $table->foreign('new_vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->foreign('updated_by')->references('admin_id')->on('admin_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_update_history');
    }
};
