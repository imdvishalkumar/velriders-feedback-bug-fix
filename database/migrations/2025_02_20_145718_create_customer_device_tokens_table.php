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
        Schema::create('customer_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('device_token', 1024)->nullable();
            $table->tinyInteger('is_deleted')->default(0)->comment('1 = Deleted, 0 = Not Deleted');
            $table->tinyInteger('is_error')->default(0)->comment('1 = Error, 0 = No Error');
            $table->json('error_log')->nullable();
            $table->tinyInteger('is_subscribed')->default(0)->comment('1 = Subscribed, 0 = Un Subscribed');

            // Define foreign key constraints
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_device_tokens');
    }
};
