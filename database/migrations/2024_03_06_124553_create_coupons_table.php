<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('code');
            $table->enum('type', ['percentage', 'fixed']); // Coupon type
            $table->decimal('percentage_discount', 5, 2)->nullable(); // Percentage discount
            $table->decimal('max_discount_amount', 8, 2)->nullable(); // Maximum discount amount
            $table->decimal('fixed_discount_amount', 8, 2)->nullable(); // Fixed discount amount
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
