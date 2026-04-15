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
        Schema::create('cancel_rental_bookings', function (Blueprint $table) {
            $table->id('cancel_id');
            $table->unsignedBigInteger('booking_id');
            $table->string('hours_diffrence')->nullable();
            $table->tinyInteger('refund_percent')->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->json('data_json')->nullable();
            $table->tinyInteger('refund_status')->default(0)->comment('0 = Not Refunded, 1 = Refunded');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancel_rental_bookings');
    }
};
