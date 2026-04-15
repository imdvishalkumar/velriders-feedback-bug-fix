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
        Schema::create('customer_referral_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('used_referral_code', 50);
            $table->string('reward_type')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('reward_amount_or_percent')->nullable();
            $table->decimal('payable_amount', 10, 2)->nullable();
            $table->tinyInteger('is_paid')->default(0)->comment('0 = Not Paid, 1 = Paid');
            $table->timestamps();

            // Define foreign key constraint
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_referral_details');
    }
};
