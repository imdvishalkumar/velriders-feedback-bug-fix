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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('booking_id');
            $table->string('razorpay_order_id', 255)->nullable();
            // $table->enum('payment_type', ['create_order', 'extend_order', 'penalty'])->default('create_order');
            $table->enum('payment_type', ['new_booking', 'extension', 'completion', 'penalty'])->default('new_booking');
            $table->string('razorpay_payment_id', 255)->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('status', 255)->nullable();
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
        Schema::dropIfExists('payments');
    }
};
