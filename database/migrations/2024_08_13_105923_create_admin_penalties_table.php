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
        Schema::create('admin_penalties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->decimal('amount', 10, 2); 
            $table->string('penalty_details')->nullable();
            $table->tinyInteger('is_paid')->default(0)->comment("0 = Not Paid, 1 = Paid");
            $table->string('razorpay_order_id')->nullable();
            $table->string('cashfree_order_id')->nullable();
            $table->timestamps();

            // Define foreign key constraint
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_penalties');
    }
};
