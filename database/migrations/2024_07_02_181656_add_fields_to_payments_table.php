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
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_mode', ['cash', 'upi', 'razorpay'])->after('status')->nullable();
            $table->string('transaction_ref_number', 255)->nullable()->after('payment_mode');
            $table->enum('payment_env', ['live', 'test'])->nullable()->after('transaction_ref_number');
            $table->string('cashfree_order_id')->nullable()->after('payment_env');
            $table->string('cashfree_payment_session_id')->nullable()->after('cashfree_order_id');
            $table->string('icici_merchant_txnNo', 100)->nullable()->after('cashfree_payment_session_id');
            $table->string('icici_txnid', 100)->nullable()->after('icici_merchant_txnNo');
            $table->enum('payment_gateway_used', ['razorpay', 'cashfree'])->nullable()->after('icici_txnid');
            $table->float('payment_gateway_charges', 8, 2)->default(0)->nullable();
            $table->string('cashfree_payment_id')->default(null)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
