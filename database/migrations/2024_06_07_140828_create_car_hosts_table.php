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
        Schema::create('car_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 5)->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->string('support_mobile_number', 15)->nullable();
            $table->string('otp', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->string('pan_number')->nullable();
            $table->date('dob')->nullable();
            $table->string('business_name')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('host_bio')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('device_token', 1024)->nullable();
            $table->string('device_id', 1024)->nullable();
            $table->string('gauth_id')->nullable();
            $table->string('gauth_type')->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->tinyInteger('registered_via')->nullable()->comment('1 - SMS, 2 - Email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_hosts');
    }
};
