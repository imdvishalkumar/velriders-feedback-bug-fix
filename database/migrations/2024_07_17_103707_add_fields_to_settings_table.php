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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('booking_gap', 50)->nullable()->after('show_all_vehicle')->comment('In Minutes');
            /*$table->string('vehicle_offer_price', 3)->nullable()->after('booking_gap')->comment('In %');
            $table->dateTime('vehicle_offer_start_date')->nullable()->after('vehicle_offer_price');
            $table->dateTime('vehicle_offer_end_date')->nullable()->after('vehicle_offer_start_date');*/
            $table->time('payment_gateway_alter_start_time')->nullable();
            $table->time('payment_gateway_alter_end_time')->nullable()->after('payment_gateway_alter_start_time');
            $table->enum('payment_gateway_type', ['razorpay', 'cashfree', 'icici'])->nullable()->after('payment_gateway_alter_end_time');
            $table->tinyInteger('reward_type')->nullable()->comment('1 = Fixed 2 = Percentage')->after('payment_gateway_type');
            $table->string('reward_val', 10)->nullable()->after('reward_type');
            $table->decimal('reward_max_discount_amount', 10, 2)->default(0)->after('reward_val');
            $table->text('reward_html')->nullable()->after('reward_max_discount_amount');
            $table->tinyInteger('cust_doc_verif_limits')->default(3)->after('reward_html');
            $table->integer('location_km_distance_val')->default(null);
            $table->string('customer_slider_title')->nullable();
            $table->string('host_slider_title')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
};
