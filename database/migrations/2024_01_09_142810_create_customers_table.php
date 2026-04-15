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
        Schema::create('customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('country_code', 5)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('otp', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->date('dob')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('business_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('shipping_address')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('device_token', 1024)->nullable();
            $table->string('device_id', 1024)->nullable();
            $table->string('gauth_id')->nullable();
            $table->string('gauth_type')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->tinyInteger('is_test_user')->default(0)->nullable()->comment('0 = Not a Test User, 1 = Test User');
            $table->tinyInteger('is_guest_user')->default(0)->nullable()->comment('0 = Not a Guest User, 1 = Guest User');
            $table->string('my_referral_code', 50)->nullable();
            $table->string('used_referral_code', 50)->nullable();
            $table->tinyInteger('registered_via')->nullable()->comment('1 - SMS, 2 - Email');
            $table->tinyInteger('govt_doc_verification_cnt')->default(1)->nullable();
            $table->tinyInteger('dl_doc_verification_cnt')->default(1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
