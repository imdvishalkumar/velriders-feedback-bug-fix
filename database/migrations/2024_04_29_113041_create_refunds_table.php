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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id('refund_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('rezorpay_refund_id', 255)->nullable();
            $table->decimal('refund_amount', 10, 2);
            $table->string('status', 255)->nullable();

            // Define foreign key constraint
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
            $table->foreign('payment_id')->references('payment_id')->on('payments')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
