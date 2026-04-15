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
        Schema::create('payment_report_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('session_id', 255)->index();
            $table->json('export_data')->comment('JSON containing: amount, account_no, ifsc_code');
            $table->boolean('is_completed')->default(false)->comment('True when is_completed=true in API call');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');
            
            // Index for faster queries by session_id
            $table->index(['session_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_report_history');
    }
};
