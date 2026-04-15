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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->tinyInteger('type')->nullable()->comment('1 = Email Notification, 2 = Push Notification');
            $table->tinyInteger('status')->default(0)->comment('0 = Not Sent, 1 = Sent');
            $table->string('message_text', 3000)->nullable();
            $table->string('event_type')->default(null);
            $table->tinyInteger('is_show')->default(0);
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
