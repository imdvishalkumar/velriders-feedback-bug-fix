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
        Schema::create('booking_transactions', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('booking_id'); // Foreign key to relate to the booking table
            $table->timestamp('timestamp'); // Timestamp of the transaction
            $table->string('type', 50); // Type of transaction (new_booking, extension, completion, etc.)
            
            // Fields from "details"
            $table->dateTime('start_date')->nullable(); // Matches "start_date"
            $table->dateTime('end_date')->nullable(); // Matches "end_date"
            $table->boolean('unlimited_kms')->nullable(); // Matches "unlimited_kms"
            $table->decimal('rental_price', 10, 2)->nullable(); // Matches "rental_price"
            $table->integer('trip_duration_minutes')->nullable(); // Matches "trip_duration_minutes"
            $table->decimal('trip_amount', 10, 2)->nullable(); // Matches "trip_amount"
            $table->decimal('tax_amt', 10, 2)->nullable(); // Matches "tax_amt"
            $table->decimal('coupon_discount', 10, 2)->nullable(); // Matches "coupon_discount"
            $table->string('coupon_code', 100)->nullable(); // Matches "coupon_code"
            $table->integer('coupon_code_id')->nullable(); // Matches "coupon id
            $table->decimal('trip_amount_to_pay', 10, 2)->nullable(); // Matches "trip_amount_to_pay"
            $table->decimal('convenience_fee', 10, 2)->nullable(); // Matches "convenience_fee"
            $table->decimal('total_amount', 10, 2)->nullable(); // Matches "total_amount"
            $table->decimal('refundable_deposit', 10, 2)->nullable(); // Matches "refundable_deposit"
            $table->decimal('final_amount', 10, 2)->nullable(); // Matches "final_amount"
            $table->string('order_type', 50)->nullable(); // Matches "order_type"

            // Additional fields for completion details
            $table->decimal('late_return', 10, 2)->nullable(); // Matches "late_return"
            $table->decimal('exceeded_km_limit', 10, 2)->nullable(); // Matches "exceeded_km_limit"
            $table->decimal('additional_charges', 10, 2)->nullable(); // Matches "additional_charges"
            $table->string('additional_charges_info', 255)->nullable(); // Matches "additional_charges_info"
            $table->decimal('amount_to_pay', 10, 2)->nullable(); // Matches "amount_to_pay"
            $table->decimal('refundable_deposit_used', 10, 2)->default(0); // Matches "refundable_deposit_used"
            $table->boolean('from_refundable_deposit')->default(false); // Matches "from_refundable_deposit"

            // Fields from "order"
            $table->boolean('paid')->default(false); // Matches "paid"
            $table->string('razorpay_order_id', 100)->nullable(); // Matches "razorpay_order_id"
            $table->string('razorpay_payment_id', 100)->nullable(); // Matches "razorpay_payment_id"

            $table->string('cashfree_order_id', 100)->nullable(); // Matches "razorpay_order_id"
            $table->string('cashfree_payment_session_id', 255)->nullable(); // Matches "razorpay_payment_id"

            $table->string('icici_merchant_txnNo', 100)->nullable();
            $table->string('icici_txnid', 100)->nullable();

            $table->boolean('refund_processed')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('razorpay_refund_id', 100)->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->decimal('vehicle_commission_amount', 10, 2)->default(0)->nullable();
            $table->decimal('vehicle_commission_tax_amt', 10, 2)->default(0)->nullable();
            // Foreign key constraint
            $table->foreign('booking_id')->references('booking_id')->on('rental_bookings')->onDelete('cascade');

            $table->timestamps(); // Laravel's created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_transactions');
    }
};
